' Machine Lot Entry Launcher (VBScript)
' This VBScript opens the machine entry page in Internet Explorer
' and allows it to be closed by JavaScript automatically

Dim IE
Set IE = CreateObject("InternetExplorer.Application")

' Configure IE settings
IE.Visible = True
IE.AddressBar = False
IE.StatusBar = False
IE.ToolBar = False
IE.MenuBar = False

' Set your server URL here (change localhost:3000 to your server address)
IE.Navigate "http://localhost:3000/machine-entry"

' Wait for page to load
Do While IE.Busy Or IE.ReadyState <> 4
    WScript.Sleep 100
Loop

' Keep script running to allow JavaScript to close the window
' The window will close automatically after successful form submission
Do While Not IE Is Nothing
    On Error Resume Next
    If Err.Number <> 0 Or IE.Visible = False Then
        Exit Do
    End If
    WScript.Sleep 500
Loop

' Clean up
Set IE = Nothing
