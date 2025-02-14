"use strict";

(function ()
{
	const pathToExtension = currentDomain + "/bitrix/mobileapp/mobile/extensions/bitrix/calls/controller/";

	class CallController
	{
		constructor()
		{
			this.callView = null;
			this._currentCall = null;
			Object.defineProperty(this, "currentCall", {
				get: () => this._currentCall,
				set: call => {
					if (this._currentCall != call)
					{
						BX.postComponentEvent("CallEvents::hasActiveCall", [!!call], "communication");
						this._currentCall = call;
					}
				}
			});
			this.childCall = null;
			this.callStartTime = null;
			this.callTimerInterval = null;
			this.callWithLegacyMobile = false;
			this.nativeCall = null;
			this.chatCounter = 0;

			this.callVideoEnabled = false; // for proximity sensor
			this.skipNextDeviceChangeEvent = false; // workaround to change device to speaker if headphones are removed

			this.onCallUserInvitedHandler = this.onCallUserInvited.bind(this);
			this.onCallUserStateChangedHandler = this.onCallUserStateChanged.bind(this);
			this.onCallUserMicrophoneStateHandler = this.onCallUserMicrophoneState.bind(this);
			this.onCallUserScreenStateHandler = this.onCallUserScreenState.bind(this);
			this.onCallUserVideoPausedHandler = this.onCallUserVideoPaused.bind(this);
			this.onCallUserVoiceStartedHandler = this.onCallUserVoiceStarted.bind(this);
			this.onCallUserVoiceStoppedHandler = this.onCallUserVoiceStopped.bind(this);
			this.onCallUserFloorRequestHandler = this.onCallUserFloorRequest.bind(this);
			this.onCallUserEmotionHandler = this.onCallUserEmotion.bind(this);
			this.onCallLocalMediaReceivedHandler = this.onCallLocalMediaReceived.bind(this);
			this.onCallLocalMediaStoppedHandler = this.onCallLocalMediaStopped.bind(this);
			this.onCallRTCStatsReceivedHandler = this.onCallRTCStatsReceived.bind(this);
			this.onCallCallFailureHandler = this.onCallCallFailure.bind(this);
			this.onCallStreamReceivedHandler = this.onCallStreamReceived.bind(this);
			this.onCallStreamRemovedHandler = this.onCallStreamRemoved.bind(this);
			this.onCallJoinHandler = this.onCallJoin.bind(this);
			this.onCallLeaveHandler = this.onCallLeave.bind(this);
			this.onCallDestroyHandler = this.onCallDestroy.bind(this);
			this.onChildCallFirstStreamHandler = this.onChildCallFirstStream.bind(this);

			this.onMicButtonClickHandler = this.onMicButtonClick.bind(this);
			this.onFloorRequestButtonClickHandler = this.onFloorRequestButtonClick.bind(this);
			this.onCameraButtonClickHandler = this.onCameraButtonClick.bind(this);
			this.onReplaceCameraClickHandler = this.onReplaceCameraClick.bind(this);
			this.onChatButtonClickHandler = this.onChatButtonClick.bind(this);
			this.onPrivateChatButtonClickHandler = this.onPrivateChatButtonClick.bind(this);
			this.onAnswerButtonClickHandler = this.onAnswerButtonClick.bind(this);
			this.onHangupButtonClickHandler = this.onHangupButtonClick.bind(this);
			this.onDeclineButtonClickHandler = this.onDeclineButtonClick.bind(this);
			this.onSetCentralUserHandler = this.onSetCentralUser.bind(this);
			this.onSelectAudioDeviceHandler = this.onSelectAudioDevice.bind(this);

			this.onNativeCallAnsweredHandler = this.onNativeCallAnswered.bind(this);
			this.onNativeCallEndedHandler = this.onNativeCallEnded.bind(this);
			this.onNativeCallMutedHandler = this.onNativeCallMuted.bind(this);
			this.onNativeCallVideoIntentHandler = this.onNativeCallVideoIntent.bind(this);

			this._nativeAnsweredAction = null;
			this.ignoreNativeCallAnswer = false;

			this.onProximitySensorDebounced = CallUtil.debounce(this.onProximitySensor.bind(this), 500);
			this.onAudioDeviceChangedDebounced = CallUtil.debounce(this.onAudioDeviceChanged.bind(this), 100);
			this.init();
		}

		init()
		{
			// init outgoing call (from chat)
			BX.addCustomEvent("onCallInvite", this.onCallInvite.bind(this));

			// new incoming call (from CallEngine)
			BX.addCustomEvent("CallEvents::incomingCall", this.onIncomingCall.bind(this));

			// try join existing call (from chat)
			BX.addCustomEvent("CallEvents::joinCall", this.onJoinCall.bind(this));

			BX.addCustomEvent("onAppActive", this.onAppActive.bind(this));
			BX.addCustomEvent("onAppPaused", this.onAppPaused.bind(this));
			BX.addCustomEvent("ImRecent::counter::messages", this.onImMessagesCounter.bind(this));

			BX.PULL.subscribe({
				type: "server",
				moduleId: "im",
				command: "callUserNameUpdate",
				callback: this.onCallUserNameUpdate.bind(this)
			});

			device.on("proximityChanged", this.onProximitySensorDebounced);
			JNVIAudioManager.on("changed", this.onAudioDeviceChangedDebounced);
		}

		onCallInvite(e)
		{
			let dialogId, dialogData;
			if ("dialogId" in e)
			{
				if (e.dialogId.startsWith("chat"))
				{
					dialogData = e.chatData || {};
				}
				else
				{
					dialogData = e.userData && e.userData[e.dialogId] || {};
				}
				dialogId = e.dialogId;

			}
			else if ("userId" in e)
			{
				dialogData = e.userData && e.userData[e.userId] || {};
				dialogId = e.userId;
			}
			else
			{
				console.error("Can not start call. No userId or dialogId in event");
				navigator.notification.alert(BX.message("MOBILE_CALL_INTERNAL_ERROR").replace("#ERROR_CODE#", "E001"));
				return;
			}
			let {name, avatar, color} = dialogData;
			avatar = decodeURI(avatar);
			this.startCall(dialogId, e.video, {name, avatar, color});
		}

		maybeShowLocalVideo(show)
		{
			return new Promise((resolve, reject) =>
			{
				if (!show)
				{
					return resolve();
				}
				if (!this.callView)
				{
					return;
				}
				MediaDevices.getUserMedia({audio: true, video: true}).then(stream =>
				{
					this.callView.setVideoStream(env.userId, stream);
					resolve();
				}).catch(err => reject(err));
			});
		}

		startCall(dialogId, video, associatedDialogData = {})
		{
			if (!this.isDeviceSupported())
			{
				console.error(BX.message("MOBILE_CALL_UNSUPPORTED_VERSION"));
				navigator.notification.alert(BX.message("MOBILE_CALL_UNSUPPORTED_VERSION"));
				return;
			}
			if (this.callView || this.currentCall)
			{
				return;
			}
			dialogId = dialogId.toString();
			let provider = BX.Call.Provider.Plain;
			let isGroupChat = dialogId.toString().substr(0, 4) === "chat";
			if (CallEngine.isCallServerAllowed() && isGroupChat)
			{
				provider = BX.Call.Provider.Voximplant;
			}

			this.requestDeviceAccess(video).then(() =>
			{
				return this.openCallView({
					status: "outgoing",
					isGroupCall: isGroupChat,
					associatedEntityName: associatedDialogData.name,
					associatedEntityAvatar: associatedDialogData.avatar,
					associatedEntityAvatarColor: associatedDialogData.color,
					cameraState: video,
					chatCounter: this.chatCounter,
				});
			}).then(() =>
			{
				BX.postComponentEvent("CallEvents::viewOpened", []);
				BX.postWebEvent("CallEvents::viewOpened", {});
				this.bindViewEvents();
				media.audioPlayer().playSound("call_start");
				return this.maybeShowLocalVideo(video && !isGroupChat);
			}).then(() =>
			{
				return CallEngine.createCall({
					entityType: "chat",
					entityId: dialogId,
					provider: provider,
					videoEnabled: !!video,
					joinExisting: isGroupChat,
				});

			}).then((createResult) =>
			{
				this.currentCall = createResult.call;
				this.bindCallEvents();
				callInterface.indicator().setMode("outgoing");
				device.setIdleTimerDisabled(true);
				device.setProximitySensorEnabled(true);

				this.callView.appendUsers(this.currentCall.getUsers());
				CallUtil.getUsers(this.currentCall.id, this.getCallUsers(true)).then(
					userData => this.callView.updateUserData(userData),
				);

				media.audioPlayer().playSound("call_outgoing", 10);
				if (createResult.isNew)
				{
					this.currentCall.inviteUsers();
				}
				else
				{
					this.callView.setState({
						status: "call",
					});

					this.currentCall.answer({
						useVideo: video,
					});
				}
			}).catch((error) =>
			{
				console.error(error);
				if (error instanceof DeviceAccessError)
				{
					this.showDeviceAccessConfirm(video, () => Application.openSettings());
				}
				else if (error instanceof CallJoinedElseWhereError)
				{
					navigator.notification.alert(BX.message("MOBILE_CALL_ALREADY_JOINED"));
				}
				else if ("code" in error && error.code === "ALREADY_FINISHED")
				{
					navigator.notification.alert("MOBILE_CALL_ALREADY_FINISHED");
				}
				else
				{
					navigator.notification.alert(BX.message("MOBILE_CALL_INTERNAL_ERROR").replace("#ERROR_CODE#", "E004"));
				}
				this.clearEverything();
			});
		}

		joinCall(callId, video)
		{
			if (this.callView || this.currentCall)
			{
				return;
			}

			this.requestDeviceAccess(video).then(() =>
			{
				return CallEngine.getCallWithId(callId);
			}).then((result) =>
			{
				this.currentCall = result.call;
				device.setIdleTimerDisabled(true);
				device.setProximitySensorEnabled(true);
				return this.openCallView({
					status: "call",
					chatCounter: this.chatCounter,
				});
			}).then(() =>
			{
				this.bindViewEvents();
				this.callView.appendUsers(this.currentCall.getUsers());
				CallUtil.getUsers(this.currentCall.id, this.getCallUsers(true)).then(
					userData => this.callView.updateUserData(userData),
				);
				this.bindCallEvents();

				this.currentCall.answer({
					useVideo: !!video,
				});
			}).catch((error) =>
			{
				console.error(error);
				if (error.code && error.code == "ALREADY_FINISHED")
				{
					navigator.notification.alert(BX.message("MOBILE_CALL_INTERNAL_ERROR").replace("#ERROR_CODE#", "E004"));
				}
				else if (error instanceof CallJoinedElseWhereError)
				{
					navigator.notification.alert(BX.message("MOBILE_CALL_ALREADY_JOINED"));
				}
				else if (error instanceof DeviceAccessError)
				{
					this.showDeviceAccessConfirm(video, () => Application.openSettings());
				}
				else
				{
					navigator.notification.alert(BX.message("MOBILE_CALL_INTERNAL_ERROR").replace("#ERROR_CODE#", "E004"));
				}
			});
		}

		onIncomingCall(e)
		{
			console.warn("incoming.call", e);

			if (!this.isDeviceSupported())
			{
				navigator.notification.alert(BX.message("MOBILE_CALL_UNSUPPORTED_VERSION"));
				return;
			}

			const newCall = CallEngine.calls[e.callId];
			this.callWithLegacyMobile = (e.isLegacyMobile === true);

			if (this.currentCall)
			{
				if (this.currentCall.id == newCall.id)
				{
					// do nothing
				}
				else if (this.currentCall.id == newCall.parentId)
				{
					if (!this.childCall)
					{
						this.childCall = newCall;
						this.childCall.users.forEach(
							userId => this.callView.addUser(userId, BX.Call.UserState.Calling),
						);
						if (e.userData)
						{
							this.callView.updateUserData(e.userData);
						}
						this.answerChildCall();
					}
				}
				else
				{
					console.warn("can't participate in two calls");
					newCall.decline(486);
				}
				return;
			}

			if (this.callView)
			{
				console.error("call view already exists");
				return;
			}

			if (newCall.associatedEntity.type === "chat" && newCall.associatedEntity.advanced["chatType"] === "videoconf")
			{
				console.error("conferences are not supported yet");
				return;
			}

			const video = e.video === true;

			this.currentCall = CallEngine.calls[newCall.id];

			if ("callservice" in window)
			{
				const nativeCall = callservice.currentCall();
				if (nativeCall && nativeCall.params.call.ID == newCall.id)
				{
					this.nativeCall = nativeCall;
					if (Application.isBackground())
					{
						console.warn(CallUtil.getDateForLog() + ": Waking up p&p");
						this.forceBackgroundConnectPull(10).then(() => {
							if (this.currentCall)
							{
								this.currentCall.repeatAnswerEvents();

								console.warn(CallUtil.getDateForLog() + ": checking self state");
								CallEngine.getRestClient().callMethod("im.call.getUserState", {callId: this.currentCall.id}).then(response => {
									let data = response.data();
									let myState = data.STATE;

									if (Application.isBackground() && myState !== "calling")
									{
										this.clearEverything();
									}
								}).catch(response => {
									console.error(response);
									if (Application.isBackground())
									{
										Application.isBackground();
									}
								});
							}
						}).catch((err) => {
							console.error("Could not connect to p&p", err);

							this.clearEverything();
						})
					}
				}
			}

			device.setIdleTimerDisabled(true);
			device.setProximitySensorEnabled(true);
			this.bindCallEvents();
			this.bindNativeCallEvents();
			this.currentCall.setVideoEnabled(video);
			this.showIncomingCall({
				video: video,
				viewStatus: e.autoAnswer ? "call" : "incoming"
			}).then(() => {
				console.warn("showIncomingCall success");
				if (this.currentCall && e.autoAnswer)
				{
					console.warn("auto-answer A");
					this.onAnswerButtonClick(video);
				}
			});
		}

		answerCurrentCall(useVideo)
		{
			media.audioPlayer().stopPlayingSound();
			if (!this.currentCall)
			{
				console.error("no call to answer");
				this.clearEverything();
				return;
			}
			this.currentCall.setVideoEnabled(useVideo);
			this.requestDeviceAccess(useVideo).then(() =>
			{
				this.currentCall.answer({
					useVideo: useVideo,
				});
				this.callView.setState({
					status: "connecting",
				});
			}).catch(error =>
			{
				console.error(error);
				if (error instanceof DeviceAccessError)
				{
					this.showDeviceAccessConfirm(
						useVideo,
						() => Application.openSettings(),
						() =>
						{
							if (this.currentCall)
							{
								this.currentCall.decline();
							}
						},
					);
				}
				else if (error instanceof CallJoinedElseWhereError)
				{
					navigator.notification.alert(BX.message("MOBILE_CALL_ALREADY_JOINED"));
				}
				else
				{
					navigator.notification.alert(BX.message("MOBILE_CALL_INTERNAL_ERROR").replace("#ERROR_CODE#", "E006"));
				}
				this.clearEverything();
			});
		}

		answerChildCall()
		{
			this.removeCallEvents();
			//this.removeVideoStrategy();
			this.childCall.on(BX.Call.Event.onStreamReceived, this.onChildCallFirstStreamHandler);
			this.childCall.on(BX.Call.Event.onLocalMediaReceived, this.onCallLocalMediaReceivedHandler);

			this.childCall.answer({
				useVideo: this.currentCall.isVideoEnabled(),
			});
		}

		showIncomingCall(params)
		{
			return new Promise((resolve, reject) => {
				if (typeof (params) != "object")
				{
					params = {};
				}
				params.video = params.video === true;

				this.openCallView({
					status: params.viewStatus || "incoming",
					isGroupCall: "id" in this.currentCall.associatedEntity && this.currentCall.associatedEntity.id.startsWith("chat"),
					associatedEntityName: this.currentCall.associatedEntity.name,
					associatedEntityAvatar: this.currentCall.associatedEntity.avatar ? CallUtil.makeAbsolute(this.currentCall.associatedEntity.avatar) : "",
					associatedEntityAvatarColor: this.currentCall.associatedEntity.avatarColor,
					isVideoCall: params.video,
					cameraState: false,
					chatCounter: this.chatCounter
				}).then(() =>
				{
					media.audioPlayer().playSound("call_incoming", 10);
					callInterface.indicator().setMode("incoming");
					this.bindViewEvents();
					let userStates = this.currentCall.getUsers();
					for (let userId in userStates)
					{
						this.callView.addUser(userId, userStates[userId]);
					}

					BX.rest.callMethod("im.call.getUsers", {
						"callId": this.currentCall.id,
						"AVATAR_HR": "Y",
					}).then(response => {
						if (this.callView)
						{
							this.callView.updateUserData(response.data())
						}
					});

					if (params.video && this.currentCall && this.currentCall.getLocalMedia)
					{
						this.currentCall.getLocalMedia().then(() => resolve());
					}
					else
					{
						resolve();
					}
				});
			});


			//this.scheduleCancelNotification();
			//window.BXIM.repeatSound('ringtone', 3500, true);
		}

		onJoinCall(callId)
		{
			if (!this.isDeviceSupported())
			{
				navigator.notification.alert(BX.message("MOBILE_CALL_UNSUPPORTED_VERSION"));
				return;
			}

			if (this.currentCall)
			{
				if (this.currentCall.id == callId)
				{
					this.unfold();
				}
				else
				{
					console.error("cannot join 2 calls yet");

				}
			}
			else
			{
				navigator.notification.confirm(
					"",
					(button) =>
					{
						if (button != 3)
						{
							this.joinCall(callId, button == 1);
						}
					},
					BX.message("MOBILE_CALL_JOIN_GROUP_CALL"),
					[
						BX.message("MOBILE_CALL_WITH_VIDEO"),
						BX.message("MOBILE_CALL_WITHOUT_VIDEO"),
						BX.message("MOBILE_CALL_MICROPHONE_CANCEL"),
					],
				)
				;
			}
		}

		bindViewEvents()
		{
			if (!this.callView)
			{
				return;
			}
			this.callView.on(CallLayout.Event.MicButtonClick, this.onMicButtonClickHandler);
			this.callView.on(CallLayout.Event.CameraButtonClick, this.onCameraButtonClickHandler);
			this.callView.on(CallLayout.Event.ReplaceCamera, this.onReplaceCameraClickHandler);
			this.callView.on(CallLayout.Event.FloorRequestButtonClick, this.onFloorRequestButtonClickHandler);
			this.callView.on(CallLayout.Event.ChatButtonClick, this.onChatButtonClickHandler);
			this.callView.on(CallLayout.Event.PrivateChatButtonClick, this.onPrivateChatButtonClickHandler);
			this.callView.on(CallLayout.Event.AnswerButtonClick, this.onAnswerButtonClickHandler);
			this.callView.on(CallLayout.Event.HangupButtonClick, this.onHangupButtonClickHandler);
			this.callView.on(CallLayout.Event.DeclineButtonClick, this.onDeclineButtonClickHandler);
			this.callView.on(CallLayout.Event.SetCentralUser, this.onSetCentralUserHandler);
			this.callView.on(CallLayout.Event.SelectAudioDevice, this.onSelectAudioDeviceHandler);
		}

		removeViewEvents()
		{
			if (!this.callView)
			{
				return;
			}
			this.callView.off(CallLayout.Event.MicButtonClick, this.onMicButtonClickHandler);
			this.callView.off(CallLayout.Event.CameraButtonClick, this.onCameraButtonClickHandler);
			this.callView.off(CallLayout.Event.ReplaceCamera, this.onReplaceCameraClickHandler);
			this.callView.off(CallLayout.Event.FloorRequestButtonClick, this.onFloorRequestButtonClickHandler);
			this.callView.off(CallLayout.Event.ChatButtonClick, this.onChatButtonClickHandler);
			this.callView.off(CallLayout.Event.PrivateChatButtonClick, this.onPrivateChatButtonClickHandler);
			this.callView.off(CallLayout.Event.AnswerButtonClick, this.onAnswerButtonClickHandler);
			this.callView.off(CallLayout.Event.HangupButtonClick, this.onHangupButtonClickHandler);
			this.callView.off(CallLayout.Event.DeclineButtonClick, this.onDeclineButtonClickHandler);
			this.callView.off(CallLayout.Event.SetCentralUser, this.onSetCentralUserHandler);
			this.callView.off(CallLayout.Event.SelectAudioDevice, this.onSelectAudioDeviceHandler);
		}

		bindCallEvents()
		{
			if (!this.currentCall)
			{
				return;
			}
			this.currentCall
				.on(BX.Call.Event.onUserInvited, this.onCallUserInvitedHandler)
				.on(BX.Call.Event.onUserStateChanged, this.onCallUserStateChangedHandler)
				.on(BX.Call.Event.onUserMicrophoneState, this.onCallUserMicrophoneStateHandler)
				.on(BX.Call.Event.onUserScreenState, this.onCallUserScreenStateHandler)
				.on(BX.Call.Event.onUserVideoPaused, this.onCallUserVideoPausedHandler)
				.on(BX.Call.Event.onUserVoiceStarted, this.onCallUserVoiceStartedHandler)
				.on(BX.Call.Event.onUserVoiceStopped, this.onCallUserVoiceStoppedHandler)
				.on(BX.Call.Event.onUserFloorRequest, this.onCallUserFloorRequestHandler)
				.on(BX.Call.Event.onUserEmotion, this.onCallUserEmotionHandler)
				.on(BX.Call.Event.onLocalMediaReceived, this.onCallLocalMediaReceivedHandler)
				.on(BX.Call.Event.onLocalMediaStopped, this.onCallLocalMediaStoppedHandler)
				.on(BX.Call.Event.onRTCStatsReceived, this.onCallRTCStatsReceivedHandler)
				.on(BX.Call.Event.onCallFailure, this.onCallCallFailureHandler)
				.on(BX.Call.Event.onStreamReceived, this.onCallStreamReceivedHandler)
				.on(BX.Call.Event.onStreamRemoved, this.onCallStreamRemovedHandler)
				.on(BX.Call.Event.onJoin, this.onCallJoinHandler)
				.on(BX.Call.Event.onLeave, this.onCallLeaveHandler)
				.on(BX.Call.Event.onDestroy, this.onCallDestroyHandler)
			;
		}

		removeCallEvents()
		{
			if (!this.currentCall)
			{
				return;
			}
			this.currentCall
				.off(BX.Call.Event.onUserInvited, this.onCallUserInvitedHandler)
				.off(BX.Call.Event.onUserStateChanged, this.onCallUserStateChangedHandler)
				.off(BX.Call.Event.onUserMicrophoneState, this.onCallUserMicrophoneStateHandler)
				.off(BX.Call.Event.onUserScreenState, this.onCallUserScreenStateHandler)
				.off(BX.Call.Event.onUserVoiceStarted, this.onCallUserVoiceStartedHandler)
				.off(BX.Call.Event.onUserVoiceStopped, this.onCallUserVoiceStoppedHandler)
				.off(BX.Call.Event.onUserFloorRequest, this.onCallUserFloorRequestHandler)
				.off(BX.Call.Event.onUserEmotion, this.onCallUserEmotionHandler)
				.off(BX.Call.Event.onLocalMediaReceived, this.onCallLocalMediaReceivedHandler)
				.off(BX.Call.Event.onLocalMediaStopped, this.onCallLocalMediaStoppedHandler)
				.off(BX.Call.Event.onRTCStatsReceived, this.onCallRTCStatsReceivedHandler)
				.off(BX.Call.Event.onCallFailure, this.onCallCallFailureHandler)
				.off(BX.Call.Event.onStreamReceived, this.onCallStreamReceivedHandler)
				.off(BX.Call.Event.onStreamRemoved, this.onCallStreamRemovedHandler)
				.off(BX.Call.Event.onJoin, this.onCallJoinHandler)
				.off(BX.Call.Event.onLeave, this.onCallLeaveHandler)
				.off(BX.Call.Event.onDestroy, this.onCallDestroyHandler)
			;
		}

		bindNativeCallEvents()
		{
			if (!this.nativeCall)
			{
				return;
			}
			this.nativeCall
				.on("answered", this.onNativeCallAnsweredHandler)
				.on("ended", this.onNativeCallEndedHandler)
				.on("muted", this.onNativeCallMutedHandler)
				.on("videointent", this.onNativeCallVideoIntentHandler)
			;
		}

		prepareWidgetLayer()
		{
			return new Promise((resolve, reject) =>
			{
				if (uicomponent.widgetLayer() && this.rootWidget)
				{
					return resolve(this.rootWidget);
				}

				uicomponent.createWidgetLayer("layout", {backdrop: {}})
					.then((rootWidget) => resolve(rootWidget))
					.catch(error => reject(error));
			});
		}

		openWidgetLayer()
		{
			return new Promise((resolve, reject) =>
			{
				this.prepareWidgetLayer()
					.then((rootWidget) =>
					{
						this.rootWidget = rootWidget;
						this.rootWidget.setListener((eventName) =>
						{
							if (eventName === "onViewRemoved")
							{
								this.rootWidget = null;
							}
						});
						return uicomponent.widgetLayer().show();
					})
					.then(() => resolve())
					.catch(error => reject(error));
			});
		}

		openCallView(viewProps = {})
		{
			return new Promise((resolve, reject) =>
			{
				this.openWidgetLayer()
					.then(() =>
					{
						console.warn("creating new CallLayout");
						this.callView = new CallLayout(viewProps);
						this.rootWidget.showComponent(this.callView);

						resolve();
					})
					.catch(error => console.error(error));
			});
		}

		fold()
		{
			if (!this.currentCall || !this.callView)
			{
				return;
			}
			let associatedAvatar = pathToExtension + "img/blank.png";
			if (this.currentCall.associatedEntity
				&& this.currentCall.associatedEntity.avatar
				&& !CallUtil.isAvatarBlank(this.currentCall.associatedEntity.avatar)
			)
			{
				associatedAvatar = encodeURI(CallUtil.makeAbsolute(this.currentCall.associatedEntity.avatar));
			}
			uicomponent.widgetLayer().hide().then(() =>
			{
				callInterface.indicator().setMode("active");
				callInterface.indicator().imageUrl = associatedAvatar;
				callInterface.indicator().show();
				callInterface.indicator().once("tap", () => this.unfold());

				BX.postComponentEvent("CallEvents::viewClosed", []);
				BX.postWebEvent("CallEvents::viewClosed", {});
			});
		}

		unfold()
		{
			callInterface.indicator().close();
			if (!this.currentCall || !this.callView)
			{
				return;
			}
			uicomponent.widgetLayer().show();

			BX.postComponentEvent("CallEvents::viewOpened", []);
			BX.postWebEvent("CallEvents::viewOpened", {});
		}

		startCallTimer()
		{
			this.callTimerInterval = setInterval(() =>
			{
				callInterface.indicator().setTime(CallUtil.formatSeconds(((new Date()).getTime() - this.callStartTime) / 1000));
			}, 1000);
		}

		stopCallTimer()
		{
			clearTimeout(this.callTimerInterval);
			this.callTimerInterval = null;
		}

		getCallUsers(includeSelf)
		{
			if (!this.currentCall)
			{
				return [];
			}
			let result = Object.keys(this.currentCall.getUsers());
			if (includeSelf)
			{
				result.push(this.currentCall.userId);
			}
			return result;
		}

		onCallUserNameUpdate(params)
		{
			let {userId, name} = params;
			if (this.callView)
			{
				this.callView.updateUserData({
					[userId]: {name}
				})
			}
		}

		onImMessagesCounter(counter)
		{
			this.chatCounter = counter;
			if (this.callView)
			{
				this.callView.setChatCounter(counter);
			}
		}

		onAppActive()
		{
			if (!this.currentCall)
			{
				console.warn("no current call");
				return;
			}
			this.currentCall.log("onAppActive");

			const push = Application.getLastNotification();

			if (
				Application.getPlatform() === 'android'
				&& !this.currentCall.isReady()
				&& push.hasOwnProperty('id')
				&& push.id.startsWith('IM_CALL_')
			)
			{
				console.log("check push");
				try
				{
					let pushParams = JSON.parse(push.params);
					console.log(pushParams);

					const callFields = pushParams.PARAMS.call;
					const isVideo = pushParams.PARAMS.video;
					const callId = callFields.ID;

					if (callId == this.currentCall.id)
					{
						console.warn("auto-answer B");
						this.answerCurrentCall(isVideo);
					}
				}
				catch (e)
				{
					// do nothing
					console.error(e);
				}
			}
			else
			{
				this.currentCall.setVideoPaused(false);

				if (!this._hasHeadphones() && JNVIAudioManager.currentDevice == "receiver")
				{
					console.warn("switching audio output to speaker on application activation");
					this._selectSpeaker();
				}
			}
		}

		onAppPaused()
		{
			if (!this.currentCall)
			{
				return;
			}

			this.currentCall.log("onAppPaused");
			this.currentCall.setVideoPaused(true);
		}

		onProximitySensor()
		{
			if (!this.currentCall)
			{
				return;
			}

			if (device.proximityState)
			{
				this.currentCall.setVideoPaused(true);
			}
			else
			{
				this.currentCall.setVideoPaused(false);
			}
		}

		onAudioDeviceChanged(deviceName)
		{
			if (Application.isBackground())
			{
				return;
			}
			console.log("onAudioDeviceChanged", deviceName);
			if (this.skipNextDeviceChangeEvent)
			{
				this.skipNextDeviceChangeEvent = false;
				return;
			}

			if (deviceName == "receiver" && this.currentCall)
			{
				this._selectSpeaker();
			}
		}

		onMicButtonClick()
		{
			if (!this.currentCall)
			{
				return;
			}
			let muted = !this.currentCall.isMuted();
			this.currentCall.setMuted(muted);
			this.callView.setMuted(muted);
			if (this.nativeCall)
			{
				this.nativeCall.mute(muted);
			}
		}

		onCameraButtonClick()
		{
			if (!this.currentCall)
			{
				return;
			}

			let cameraEnabled = !this.currentCall.isVideoEnabled();

			if (this.callWithLegacyMobile)
			{
				navigator.notification.alert(
					BX.message("MOBILE_CALL_NO_CAMERA_WITH_LEGACY_APP"),
					() => {},
					BX.message("MOBILE_CALL_ERROR"));
				return;
			}

			if (cameraEnabled)
			{
				MediaDevices.requestCameraAccess().then(() =>
				{
					this.currentCall.setVideoEnabled(cameraEnabled);
					this.callView.setCameraState(cameraEnabled); // should we update button state only if we received local video?
				}).catch(() =>
				{
					navigator.notification.alert(
						BX.message("MOBILE_CALL_MICROPHONE_CAN_NOT_ACCESS_CAMERA"),
						() => {},
						BX.message("MOBILE_CALL_MICROPHONE_ACCESS_DENIED"));
				});
			}
			else
			{
				this.currentCall.setVideoEnabled(cameraEnabled);
				this.callView.setCameraState(cameraEnabled); // should we update button state only if we received local video?
			}
		}

		onReplaceCameraClick()
		{
			if (!this.currentCall && !this.currentCall.videoEnabled)
			{
				return;
			}
			this.currentCall.switchCamera();
			setTimeout(() => this.callView.setMirrorLocalVideo(this.currentCall.isFrontCameraUsed()), 1000);
		}

		onChatButtonClick()
		{
			this.fold();
			if (this.currentCall)
			{
				BX.postComponentEvent("onOpenDialog", [{dialogId: this.currentCall.associatedEntity.id}, true], "im.recent");
			}
		}

		onPrivateChatButtonClick(userId)
		{
			this.fold();
			BX.postComponentEvent("onOpenDialog", [{dialogId: userId}, true], "im.recent");
		}

		onFloorRequestButtonClick()
		{
			let floorState = this.callView.getUserFloorRequestState(env.userId);
			let talkingState = this.callView.getUserTalking(env.userId);

			this.callView.setUserFloorRequestState(env.userId, !floorState);

			if (this.currentCall)
			{
				this.currentCall.requestFloor(!floorState);
			}

			clearTimeout(this.callViewFloorRequestTimeout);
			if (talkingState && !floorState)
			{
				this.callViewFloorRequestTimeout = setTimeout(() =>
				{
					if (this.currentCall)
					{
						this.currentCall.requestFloor(false);
					}
				}, 1500);
			}
		}

		onAnswerButtonClick(useVideo)
		{
			console.log(CallUtil.getDateForLog() + " onAnswerButtonClick");

			this.answerCurrentCall(useVideo);
			if (this.nativeCall)
			{
				console.log("looks like the native call is not answered , calling answer");
				this.ignoreNativeCallAnswer = true;
				this.nativeCall.answer();
			}
		}

		onHangupButtonClick()
		{
			if (this.currentCall)
			{
				this.currentCall.hangup();
			}
			this.clearEverything();
		}

		onDeclineButtonClick()
		{
			if (this.currentCall)
			{
				this.currentCall.decline();
			}
			this.clearEverything();
		}

		onSetCentralUser(userId)
		{
			if (this.currentCall && this.currentCall.allowVideoFrom)
			{
				this.currentCall.allowVideoFrom([userId]);
			}
		}

		onSelectAudioDevice(deviceName)
		{
			this.skipNextDeviceChangeEvent = true;
			JNVIAudioManager.selectAudioDevice(deviceName);
		}

		onCallUserInvited(e)
		{
			if (this.callView)
			{
				this.callView.addUser(e.userId);
				CallUtil.getUsers(this.currentCall.id, [e.userId]).then(
					userData => this.callView.updateUserData(userData),
				);
			}
		}

		onCallUserStateChanged(userId, state, prevState, isLegacyMobile)
		{
			if (this.callView)
			{
				this.callView.setUserState(userId, state);
			}
			if (state === BX.Call.UserState.Connecting || state === BX.Call.UserState.Connected)
			{
				media.audioPlayer().stopPlayingSound();
			}
			if (state === BX.Call.UserState.Connected && !this.callStartTime)
			{
				this.callStartTime = (new Date()).getTime();
				callInterface.indicator().setMode("active");
				this.startCallTimer();
			}
			if (state === BX.Call.UserState.Connected && this._nativeAnsweredAction)
			{
				this._nativeAnsweredAction.fullfill();
				this._nativeAnsweredAction = null;
			}
			if (isLegacyMobile)
			{
				this.callWithLegacyMobile = true;
			}
		}

		onCallUserMicrophoneState(userId, microphoneState)
		{
			if (this.callView)
			{
				this.callView.setUserMicrophoneState(userId, microphoneState);
			}
		}

		onCallUserScreenState(userId, screenState)
		{
			if (this.callView)
			{
				this.callView.setUserScreenState(userId, screenState);
			}
		}

		onCallUserVideoPaused(userId, videoPaused)
		{
			if (this.callView)
			{
				this.callView.setUserVideoPaused(userId, videoPaused);
			}
		}

		onCallUserVoiceStarted(userId)
		{
			if (this.callView)
			{
				this.callView.setUserTalking(userId, true);
				// this.callView.setUserFloorRequestState(userId, false); (is set in layout automatically to prevent racing condition
			}
		}

		onCallUserVoiceStopped(userId)
		{
			if (this.callView)
			{
				this.callView.setUserTalking(userId, false);
			}
		}

		onCallUserFloorRequest(userId, requestActive)
		{
			if (this.callView)
			{
				this.callView.setUserFloorRequestState(userId, requestActive);
			}
		}

		onCallUserEmotion()
		{

		}

		onCallLocalMediaReceived(localStream)
		{
			if (this.callView)
			{
				this.callView.setVideoStream(env.userId, localStream, this.currentCall.isFrontCameraUsed());
			}
		}

		onCallLocalMediaStopped()
		{
			if (this.callView)
			{
				this.callView.setVideoStream(env.userId, null);
			}
		}

		onCallRTCStatsReceived()
		{

		}

		onCallCallFailure()
		{
			console.error("onCallFailure");
			this.clearEverything();
			navigator.notification.alert(BX.message("MOBILE_CALL_INTERNAL_ERROR").replace("#ERROR_CODE#", "E003"));
		}

		onCallStreamReceived(userId, stream)
		{
			if (this.callView)
			{
				this.callView.setVideoStream(userId, stream);
			}
		}

		onCallStreamRemoved(userId)
		{
			if (this.callView)
			{
				this.callView.setVideoStream(userId, null);
			}
		}

		onChildCallFirstStream(userId, stream)
		{
			this.currentCall.log("Finishing one-to-one call, switching to group call");

			this.callView.setVideoStream(userId, stream);
			this.childCall.off(BX.Call.Event.onStreamReceived, this.onChildCallFirstStreamHandler);

			this.removeCallEvents();
			var oldCall = this.currentCall;
			oldCall.hangup();

			this.currentCall = this.childCall;
			this.childCall = null;

			if (oldCall.muted)
			{
				this.currentCall.setMuted(true);
			}

			this.bindCallEvents();
			//this.createVideoStrategy();
		}

		onCallJoin(e)
		{
			if (e.local)
			{
				console.warn("joined local call");
				if (!this._hasHeadphones() && JNVIAudioManager.currentDevice == "receiver" && !Application.isBackground())
				{
					console.warn("no headphones");
					this._selectSpeaker();
				}

				return;
			}
			// remote answer, stop ringing and hide incoming cal notification
			this.clearEverything();
		}

		onCallLeave(e)
		{
			if (!e.local && this.currentCall && this.currentCall.ready)
			{
				console.error(new Error("received remote leave with active call!"));
				return;
			}

			this.clearEverything();
		}

		onCallDestroy()
		{
			this.clearEverything();
		}

		onNativeCallAnswered(nativeAction)
		{
			console.log(CallUtil.getDateForLog() + " onNativeCallAnswered");

			if (this.nativeCall)
			{
				this._nativeAnsweredAction = nativeAction;

				if (!this.ignoreNativeCallAnswer)
				{
					this.answerCurrentCall(this.nativeCall.params.video);
				}
			}
		}

		onNativeCallEnded(nativeAction)
		{
			if (this.nativeCall && this.nativeCall.connected)
			{
				this.onHangupButtonClick();
			}
			else
			{
				this.onDeclineButtonClick();
			}
			// todo: remove if (nativeAction) :)
			if (nativeAction)
			{
				setTimeout(() => nativeAction.fullfill(), 500);
			}
		}

		onNativeCallMuted(muted)
		{
			this.currentCall.setMuted(muted);
			this.callView.setMuted(muted);
		}

		onNativeCallVideoIntent()
		{
			setTimeout(() => this.onCameraButtonClick(), 1000);
		}

		clearEverything()
		{
			console.trace("clearEverything");
			if (this.currentCall)
			{
				this.removeCallEvents();
				this.currentCall = null;
			}
			if (this._nativeAnsweredAction)
			{
				this._nativeAnsweredAction.fail();
				this._nativeAnsweredAction = null;
			}
			if (this.nativeCall)
			{
				this.nativeCall.finish();
				this.nativeCall = null;
			}
			this.ignoreNativeCallAnswer = false;

			if (uicomponent.widgetLayer())
			{
				uicomponent.widgetLayer().close();
			}
			this.rootWidget = null;
			this.callView = null;
			callInterface.indicator().close();
			this.callStartTime = null;
			this.stopCallTimer();
			media.audioPlayer().stopPlayingSound();
			device.setIdleTimerDisabled(false);
			device.setProximitySensorEnabled(false);
			this.callWithLegacyMobile = false;
			this.callVideoEnabled = false;
			this.skipNextDeviceChangeEvent = false;

			BX.postComponentEvent("CallEvents::viewClosed", []);
			BX.postWebEvent("CallEvents::viewClosed", {});
		}

		isDeviceSupported()
		{
			return Application.getApiVersion() >= 36;
		}

		requestDeviceAccess(withVideo)
		{
			return new Promise((resolve, reject) =>
			{
				MediaDevices.requestMicrophoneAccess().then(
					() => {
						if (!withVideo)
						{
							return resolve();
						}
						MediaDevices.requestCameraAccess().then(
							() => resolve()
						).catch(({justDenied}) => reject(new DeviceAccessError(justDenied)))
					}
				).catch(({justDenied}) => reject(new DeviceAccessError(justDenied)));
			});
		}

		showDeviceAccessConfirm(withVideo, acceptCallback = () => { }, declineCallback = () => { })
		{
			return new Promise((resolve) =>
			{
				navigator.notification.confirm(
					withVideo ? BX.message("MOBILE_CALL_MICROPHONE_CAMERA_REQUIRED") : BX.message("MOBILE_CALL_MICROPHONE_REQUIRED"),
					(button) => button == 1 ? acceptCallback() : declineCallback(),
					withVideo ? BX.message("MOBILE_CALL_NO_MICROPHONE_CAMERA_ACCESS") : BX.message("MOBILE_CALL_NO_MICROPHONE_ACCESS"),
					[
						BX.message("MOBILE_CALL_MICROPHONE_SETTINGS"),
						BX.message("MOBILE_CALL_MICROPHONE_CANCEL"),
					],
				);
			});
		}

		forceBackgroundConnectPull(timeoutSeconds = 10)
		{
			return new Promise((resolve, reject) => {

				if (CallEngine.pullStatus === 'online')
				{
					resolve();
					return;
				}

				var onConnectTimeout = function()
				{
					console.error("Timeout while waiting for p&p to connect");
					BX.removeCustomEvent("onPullStatus", onPullStatus);
					reject('connect timeout');
				};
				var connectionTimeout = setTimeout(onConnectTimeout, timeoutSeconds * 1000);

				var onPullStatus = ({status, additional}) =>
				{
					if (status === 'online')
					{
						BX.removeCustomEvent("onPullStatus", onPullStatus);
						clearTimeout(connectionTimeout);
						resolve();
					}

					if (status === 'offline' && additional.isError) // offline is fired on errors too
					{
						BX.removeCustomEvent("onPullStatus", onPullStatus);
						clearTimeout(connectionTimeout);
						reject('connect error');
					}
				};

				BX.addCustomEvent("onPullStatus", onPullStatus);
				BX.postComponentEvent("onPullForceBackgroundConnect", [], "communication");
			});
		}

		_hasHeadphones()
		{
			return JNVIAudioManager.availableAudioDevices.some(d => d === "wired" || d === "bluetooth");
		}

		_selectSpeaker()
		{
			this.skipNextDeviceChangeEvent = true;
			JNVIAudioManager.selectAudioDevice("speaker");
		}

		test(status = "call", viewProps = {})
		{
			let users = [3, 4 /*,5, 6, 7, 8, 9, 10, 11, 12 /*13, 14, 15*/, 464, 473];
			this.openCallView({
				status: status,
				isGroupCall: true,
				isVideoCall: true,
				associatedEntityName: "Very very long chat name. Very very long chat name. Very very long chat name. And again.",
				associatedEntityAvatar: "",
				...viewProps,
			}).then(() =>
			{

				this.bindViewEvents();
				users.forEach(userId => this.callView.addUser(userId, BX.Call.UserState.Connected));
				this.callView.pinUser(3);

				BX.rest.callMethod("im.user.list.get", {
					"ID": users.concat(env.userId),
					"AVATAR_HR": "Y",
				}).then(response => this.callView.updateUserData(response.data()));

				console.log("trying get video");
				MediaDevices.getUserMedia({audio: true, video: true}).then(stream =>
				{
					console.log("video track received");
					console.log(stream.getTracks());
					this.stream = stream;
					this.callView.setVideoStream(env.userId, stream);
				}).catch(err => console.error(err));
			});
		}

		testMenu(count = 5, withSeparators = false)
		{
			let items = [];
			for (let i = 0; i < count; i++)
			{
				items.push({
					text: `items ${i}`,
					iconClass: "returnToSpeaker",
					onClick: () => console.log(`item ${i} click`),
				});
				if (withSeparators)
				{
					items.push({separator: true});
				}
			}

			this.menu = new CallMenu({
				items: [
					{
						text: "zxc iop",
						iconClass: "participants",
						showSubMenu: true,
						color: "#FF0000",
						onClick: () =>
						{
							console.log(456);
						},
					},
					{separator: true},
					...items,
				],
				onClose: () =>
				{
					console.log("test menu closed");
					this.menu && this.menu.destroy();
					uicomponent.widgetLayer() && uicomponent.widgetLayer().close();
				},
				onDestroy: () =>
				{
					this.menu = null;
				},
			});

			this.openWidgetLayer().then(() => this.menu.show())
				.then(() => console.log("success"))
				.catch((e) => console.error(e));
		}

		testDevices()
		{
			let menuItems = JNVIAudioManager.availableAudioDevices.map(deviceAlias =>
			{
				return {
					text: deviceAlias,
					selected: deviceAlias === JNVIAudioManager.currentDevice,
					onClick: () =>
					{
						if (this.menu)
						{
							this.menu.close();
						}
						JNVIAudioManager.selectAudioDevice(deviceAlias);
					},
				};
			});
			menuItems.push({
				separator: true,
			});
			menuItems.push({
				text: BX.message("MOBILE_CALL_MENU_CANCEL"),
				onClick: () =>
				{
					if (this.menu)
					{
						this.menu.close();
					}
				},
			});

			console.log(menuItems);

			this.menu = new CallMenu({
				items: menuItems,
				onClose: () => this.menu.destroy(),
				onDestroy: () =>
				{
					this.menu = null;
					uicomponent.widgetLayer().close();
				},
			});

			this.openWidgetLayer().then(() => this.menu.show())
				.then(() => console.log("success"))
				.catch((e) => console.error(e));
		}

		ttt()
		{
			console.log(pathToExtension + "img/blank.png");
			callInterface.indicator().setMode("active");
			callInterface.indicator().imageUrl = pathToExtension + "img/blank.png";
			callInterface.indicator().show();
		}
	}

	if ("uicomponent" in window && uicomponent.widgetLayer())
	{
		uicomponent.widgetLayer().close();
	}
	if (Application.getApiVersion() >= 36)
	{
		setTimeout(() => window.callController = new CallController(), 500);
	}
})();