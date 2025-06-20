from flask import Flask, render_template_string
import os

app = Flask(__name__)

TEMPLATE = '''
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>PowerShell Mail-Überwachungs-Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 800px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px #0001; padding: 32px; }
        h1 { color: #2c3e50; }
        p { color: #555; }
        .logbox {
            background: #222; color: #0f0; font-family: monospace;
            padding: 16px; border-radius: 6px; margin-top: 24px;
            height: 400px; overflow-y: scroll; white-space: pre-wrap;
            box-shadow: 0 1px 4px #0002;
        }
        .empty { color: #888; font-style: italic; }
    </style>
</head>
<body>
    <div class="container">
        <h1>PowerShell Mail-Überwachungs-Dashboard</h1>
        <p>Hier siehst du den Status deines PowerShell-Mail-Skripts. Das Dashboard zeigt, ob das Skript auf Anfragen lauscht und wann eine E-Mail versendet wurde.</p>
        <div class="logbox">{{ log_content|safe }}</div>
    </div>
</body>
</html>
'''

@app.route('/')
def show_log():
    if os.path.exists('mail_log.txt'):
        with open('mail_log.txt', encoding='utf-8') as f:
            content = f.read().strip()
        if not content:
            log_content = '<span class="empty">Noch keine Aktivitäten protokolliert.</span>'
        else:
            log_content = content.replace('\n', '<br>')
    else:
        log_content = '<span class="empty">Noch keine Logdatei vorhanden.</span>'
    return render_template_string(TEMPLATE, log_content=log_content)

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=8025) 