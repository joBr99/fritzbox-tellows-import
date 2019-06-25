# fritzbox-tellows-import
PHP Script that downloads tellow Score list and uploads it to fritzbox phonebooks


Anleitung zur Installation und Nutzung gibt's hier:

https://byte-wiese.de/2018/07/tellows-scorelisten-automatisch-in-ein-fritzbox-telefonbuch-importieren/






### Setup Instructions ###

1. Clone Repository to a location of your choise
`git clone –-recursive https://github.com/joBr99/fritzbox-tellows-import.git`
2. Copy configuration file
`cp config.example.php config.php`
3. Adjust configuration to fit your needs
4. Configure an cronjob or something similar to run the PHP Script periodically.
`(crontab -l ; echo "0 0 */10 * * php /opt/fritzbox-tellows-import/tellows-import.php >/dev/null 2>&1
") | crontab -`



### Feedback, license, support ###
The whole work is licensed under a Creative Commons cc-by-sa license (http://creativecommons.org/licenses/by-sa/3.0/de/). You are free to use and modify it, even for commercial use. If you redistribute it, you have to ensure my name is kept in the code and you use the same conditions.
