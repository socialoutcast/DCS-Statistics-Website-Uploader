<h2>Requirements</h2>
<ul>
  <li>
    <a href="https://github.com/Special-K-s-Flightsim-Bots/DCSServerBot/releases" target="_blank">
      DCSServerBot By Special K
    </a>
  </li>
  <li>Python v3.13.3 or above – (this should already be installed to use DCSServerBot)</li>
  <li>PHP 8.3 Web server with FTP</li>
  <li>DCSServerBot – dbexporter Module</li>
</ul>

<h2>Installation With Remote Webserver</h2>
<ol>
  <li>
    Install the DCSServerBot Dbexporter Module:<br>
    <a href="https://github.com/Special-K-s-Flightsim-Bots/DCSServerBot/blob/master/plugins/dbexporter/README.md" target="_blank">
      DCSServerBot Dbexporter README
    </a>
  </li>

  <li>
    Extract the latest version of the DCS Statistics Module. Place the Stats-Uploader files somewhere convenient (e.g., <code>Documents</code>).
  </li>

  <li>
    <strong>Optional Edits:</strong><br>
    Only edit the text highlighted — anything else could break the script.
    <ul>
      <li>Open <code>index.php</code> found in <code>dcs-stats</code></li>
      <li>Line 6: <code>&lt;title&gt;DCS Statistics Dashboard&lt;/title&gt;</code></li>
      <li>Line 50: <code>&lt;h1&gt;Player Statistics Dashboard&lt;/h1&gt;</code></li>
      <li>Line 54:
        <pre>&lt;a href="index.php" style="margin: 0 15px; text-decoration: none; font-weight: bold; color: #333;"&gt;Home&lt;/a&gt;</pre>
      </li>
      <li>Line 55:
        <pre>&lt;a href="https://discord.com" target="_blank" style="margin: 0 15px; text-decoration: none; font-weight: bold; color: #333;"&gt;Discord&lt;/a&gt;</pre>
      </li>
      <li>
        Update the Discord URL to your Discord server.<br>
        Optionally, change <code>index.php</code> to your main homepage URL (e.g., <code>http://yourdomain.com</code>).
      </li>
      <li>Save your changes.</li>
    </ul>
  </li>

  <li>Upload the <code>dcs-stats</code> folder to your web host.</li>

  <li>
    Obtain your FTP Username, Password & Folder Location.<br>
    It’s recommended to use a dedicated FTP account that points directly to the folder (e.g., <code>home/user/domain/dcs-stats/data</code>).
  </li>

  <li>
    Go to the Stats-Uploader directory you moved to <code>Documents</code> in step 2 and open the <code>config.ini</code> file.
  </li>

  <li>
    You will see the files that can be uploaded — defaults have been set for you.
  </li>

  <li>
    At the top of the page, configure the following in <code>config.ini</code>:
    <ul>
      <li><strong>Local_folder</strong>: Path to your DCSServerBot export folder</li>
      <li><strong>Remote_folder</strong>: Path inside your FTP server (e.g., <code>/home/user/domain/dcs-stats/data</code>)</li>
      <li><strong>Host</strong>: Your domain name or server address</li>
      <li><strong>User</strong>: Your FTP username</li>
      <li><strong>Password</strong>: Your FTP password</li>
    </ul>
  </li>

  <li>
    Run <code>uploader.py</code>.<br>
    <em>Note: <code>missionstats.json</code> may take a few minutes to upload as it contains the bulk of your data.</em><br>
    Once complete, a 60-minute countdown will start until the next run.
  </li>

  <li>
    Visit <code>http://mydomain.com/dcs-stats</code> in your browser — your statistics should now be visible.
  </li>
</ol>

<p><strong>Enjoy the statistics!</strong></p>
<p><em>Note: If an update is released, you will need to reapply any optional edits.</em></p>

