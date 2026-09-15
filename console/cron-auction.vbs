' Bargain auction cron — hidden runner for Task Scheduler.
' Use this instead of cron-auction.bat so no CMD window flashes.
' Task: BargainAuctionTick
' Program: wscript.exe
' Arguments: //B //Nologo "c:\inetpub\wwwroot\tevin\console\cron-auction.vbs"

Option Explicit

Dim sh, root, php, cmd
Set sh = CreateObject("WScript.Shell")

root = CreateObject("Scripting.FileSystemObject").GetParentFolderName(WScript.ScriptFullName)
root = CreateObject("Scripting.FileSystemObject").GetParentFolderName(root)

php = "C:\Program Files\PHP\php-win.exe"
If Not CreateObject("Scripting.FileSystemObject").FileExists(php) Then
  php = "C:\Program Files\PHP\php.exe"
End If

sh.CurrentDirectory = root
cmd = "cmd /c ""if not exist console\runtime\logs mkdir console\runtime\logs" _
  & " & """ & php & """ yii auction/tick >> console\runtime\logs\cron-auction.log 2>&1"""

' 0 = hidden window, False = do not wait
sh.Run cmd, 0, False
