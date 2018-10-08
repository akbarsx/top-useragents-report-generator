# top-useragents-report-generator
Generating a report on top user agents for matomoo (ex. piwik) [device-detector](https://github.com/matomo-org/device-detector).

### Listing all user agents from your logs
Sometimes it may be useful to generate the list of most used user agents on your website,
extracting this list from your access logs using the following command:

```
zcat ~/path/to/access/logs* | awk -F'"' '{print $6}' | sort | uniq -c | sort -rn | head -n20000 > /home/matomo/top-user-agents.txt
```

### Use this simple scripts

1. Clone repo
2. Install dependencies (`composer install`)
3. Add your `top-user-agents.txt` file in `data` directory
4. Run `domain.tld/report.php` or `# php report.php` (in console, for large UserAgents files)
5. Look at the results :)


PS: this script writed by hand less than one hour. Its just for send issue and (maybe) for to help add new devices in matomo device-detector package.
