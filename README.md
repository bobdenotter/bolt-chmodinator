Bolt Chmodinator
================

Welcome to the <strong>Chmodinator</strong>!

If you're having issues with your Bolt files not being removable using your FTP
client or vice-versa, this extension will help you sort it out by applying
'chmod' to set the correct permissions to it. Clicking the 'Fix' button will try
to make all files in the data folders writable to 'all', and it will inform you
of any files it couldn't modify, so you can change them using the command line
or your (S)FTP client.

After installation, you can find the Chmodinator under 'Extensions' > 'Chmodinator'.

![extras](https://cloud.githubusercontent.com/assets/1833361/10888047/4cb04bce-818a-11e5-8398-970f327b9e25.png)

You should be aware that using this tool is not considered 'good practice'. If
possible, you should work with your system administrator to get things set up
properly. If that's not an option, or if you're on shared hosting, this
extension will help you out!

Sample output / screenshot:

![chmodinator screenshot](https://cloud.githubusercontent.com/assets/1833361/10887032/34f597a0-8185-11e5-9cd8-409a2f5302d7.png)

Running Chmodinator from the command line / cronjob
---------------------------------------------------

If you wish to incorporate running Chmodinator into your deploy-script or 
regular maintenance, you can use something like this: 

```
wget http://example.org/bolt/extensions/chmodinator/fix?key=secretkey 
```

or 

```
wget http://example.org/bolt/extensions/chmodinator/wipe?key=secretkey 
```

In order for this to work, you'll need to set a proper key in the 
`app/config/extensions/chmodinator.bobdenotter.yml` file. 

You can also run it every night, with your cronjobs:

```
0 4 * * * wget http://example.org/bolt/extensions/chmodinator/fix?key=secretkey >/dev/null 2>&1
```

Chmodinator and the debug toolbar
---------------------------------

If you are running with `debug: true`, you'll get an alert on each "wipe" 
action. This is because the profiler information gets wiped, before it can 
be shown.
 
The best way to prevent this is to turn off `debug` in production. Which you
should be doing, regardless. ;-)


