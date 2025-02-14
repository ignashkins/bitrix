import './style.css';
import * as Mixins from "../base/components/mixins";

const FieldFileItem = {
	props: ['field', 'itemIndex', 'item'],
	template: `
		<div>
			<div v-if="file.content" class="b24-form-control-file-item">
				<div class="b24-form-control-file-item-preview">
					<img class="b24-form-control-file-item-preview-image" 
						:src="fileIcon"
						v-if="hasIcon"
					>
				</div>
				<div class="b24-form-control-file-item-name">
					<span class="b24-form-control-file-item-name-text">
						{{ file.name }}
					</span>
					<div style="display: none;" class="b24-form-control-file-item-preview-image-popup">
						<img>
					</div>
				</div>
				<div @click.prevent="removeFile" class="b24-form-control-file-item-remove"></div>
			</div>
			<div v-show="!file.content" class="b24-form-control-file-item-empty">
				<label class="b24-form-control">
					{{ field.messages.get('fieldFileChoose') }}
					<input type="file" style="display: none;"
						ref="inputFiles"
						@change="setFiles"
						@blur="$emit('input-blur')"
						@focus="$emit('input-focus')"
					>
				</label>
			</div>
		</div>
	`,
	computed: {
		value: {
			get: function () {
				let value = this.item.value || {};
				if (value.content)
				{
					return JSON.stringify(this.item.value);
				}

				return '';
			},
			set: function (newValue) {
				newValue = newValue || {};
				if (typeof newValue === 'string')
				{
					newValue = JSON.parse(newValue);
				}
				this.item.value = newValue;
				this.item.selected = !!newValue.content;

				this.field.addSingleEmptyItem();
			}
		},
		file: function () {
			return this.item.value || {};
		},
		hasIcon ()
		{
			return this.file.type.split('/')[0] === 'image';
		},
		fileIcon ()
		{
			return (this.hasIcon
				? 'data:' + this.file.type + ';base64,' + this.file.content
				: null
			);
		},
	},
	methods: {
		setFiles() {
			let file = this.$refs.inputFiles.files[0];
			if (!file)
			{
				this.value = null;
			}
			else
			{
				let reader = new FileReader();
				reader.onloadend = () => {
					const result = reader.result.split(';');
					this.value = {
						name: file.name,
						size: file.size,
						type: result[0].split(':')[1],
						content: result[1].split(',')[1],
					};
				};
				reader.readAsDataURL(file);
			}
		},
		removeFile() {
			this.value = null;
			this.field.removeItem(this.itemIndex);
			this.$refs.inputFiles.value = null;
		}
	}
};

const FieldFile = {
	mixins: [Mixins.MixinField],
	components: {
		'field-file-item': FieldFileItem,
	},
	template: `
		<div class="b24-form-control-container">
			<div class="b24-form-control-label">
				{{ field.label }}
				<span v-show="field.required" class="b24-form-control-required">*</span>
			</div>
			<div class="b24-form-control-filelist">
				<field-file-item
					v-for="(item, itemIndex) in field.items"
					v-bind:key="field.id"
					v-bind:field="field"
					v-bind:itemIndex="itemIndex"
					v-bind:item="item"
					@input-blur="$emit('input-blur')"
					@input-focus="$emit('input-focus')"
				></field-file-item>
				<field-item-alert v-bind:field="field"></field-item-alert>
			</div>
		</div>
	`,
	created()
	{
		if (this.field.multiple)
		{
			this.field.addSingleEmptyItem();
		}
	},
	computed: {

	},
	methods: {

	}
};

export {
	FieldFile,
}