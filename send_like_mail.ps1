$logFile = "C:\html\mail_log.txt"
function Write-Log($msg) {
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    "$timestamp $msg" | Out-File -FilePath $logFile -Append
    Write-Host $msg
}

# Listener für HTTP-POST-Anfragen auf Port 8088
$listener = [System.Net.HttpListener]::new()
$listener.Prefixes.Add("http://+:8088/")
try {
    $listener.Start()
    Write-Log "Listening on port 8088..."
} catch {
    Write-Log "Fehler beim Starten des Listeners: $_"
    exit 1
}

while ($true) {
    try {
        $context = $listener.GetContext()
        $request = $context.Request

        # Nur POST-Anfragen mit Content-Type application/json verarbeiten
        if ($request.HttpMethod -eq "POST" -and $request.ContentType -like "application/json*") {
            if ($null -ne $request.InputStream -and $request.ContentLength64 -gt 0) {
                $reader = [System.IO.StreamReader]::new($request.InputStream)
                $body = $reader.ReadToEnd()
                $reader.Close()

                if (![string]::IsNullOrWhiteSpace($body)) {
                    try {
                        $data = $body | ConvertFrom-Json
                        if ($data -and $data.to) {
                            # Mailjet SMTP-Konfiguration
                            $smtpServer = "in-v3.mailjet.com"
                            $smtpFrom = "fabian.baeggli@icloud.com"
                            $smtpUser = "5daf67ddfb7763d8b01fa8d86551c0e0"
                            $smtpPass = "837e16eb03a118b72d1c528a5fbba6e0"
                            $securePass = ConvertTo-SecureString $smtpPass -AsPlainText -Force
                            $cred = New-Object System.Management.Automation.PSCredential($smtpUser, $securePass)

                            Send-MailMessage -From $smtpFrom -To $data.to -Subject $data.subject -Body $data.body -SmtpServer $smtpServer -Port 587 -UseSsl -Credential $cred
                            Write-Log "Mail sent to $($data.to)"
                        } else {
                            Write-Log "Fehlende oder ungültige Daten im Request."
                        }
                    } catch {
                        Write-Log "Fehler beim Verarbeiten der Anfrage: $_"
                    }
                } else {
                    Write-Log "Leerer Request-Body empfangen."
                }
            } else {
                Write-Log "Leerer InputStream oder ContentLength = 0."
            }
        } else {
            Write-Log "Nicht unterstützte Anfrage: $($request.HttpMethod) $($request.ContentType)"
        }

        $response = $context.Response
        $response.StatusCode = 200
        $response.Close()
    } catch {
        Write-Log "Fehler im Listener: $_"
        Start-Sleep -Seconds 1
    }
}