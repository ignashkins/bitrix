function EditDocWithProgID(file)
{
	if(!(document.attachEvent && !(navigator.userAgent.toLowerCase().indexOf('opera') != -1)))
	{
		return true;
	}
	
	try
	{
		var EditDocumentButton = new ActiveXObject("SharePoint.OpenDocuments.2");
		if (EditDocumentButton)
		{
			var url = location.protocol + "//" + location.host + file;
			if(EditDocumentButton.EditDocument2(window, url))
			{
				return false;
			}
		}
	}
	catch(e){}
	return true;
}
function WDDrop(oAnchor)
{
	var sSaveLocation = oAnchor; 
	if (oAnchor && typeof(oAnchor) == "object")
		sSaveLocation = oAnchor.href;
	if (confirm(oText['message01']))
		jsUtils.Redirect({}, sSaveLocation);
}
function WDAddElement(oAnchor)
{
	var sSaveLocation = oAnchor; 
	if (oAnchor && typeof(oAnchor) == "object")
		sSaveLocation = oAnchor.href;

    var sTemplate = location.protocol + "//" + location.host + '/bitrix/components/bitrix/webdav.menu/template.doc';
    
	try	{
		var AddDocumentButton = new ActiveXObject("SharePoint.OpenDocuments.2");
		if (!AddDocumentButton.CreateNewDocument2(window, sTemplate, sSaveLocation))
		{
			alert(oText['error_create_1']);
		}
		
		AddDocumentButton.PromptedOnLastOpen();
		SetWindowRefreshFocus();
		return;
	} catch (e) { }
	
	try {
		AddDocumentButton = new ActiveXObject("SharePoint.OpenDocuments.1");
		window.onfocus = null;
		
		if (!AddDocumentButton.CreateNewDocument(sTemplate, sSaveLocation))
		{
			alert(oText['error_create_1']);
		}
		
		SetWindowRefreshFocus();
		return;
	} catch (e) { alert(oText['error_create_2']); }
}
function SetWindowRefreshFocus()
{
	window.onfocus = new Function("window.location.href=window.location;");
}