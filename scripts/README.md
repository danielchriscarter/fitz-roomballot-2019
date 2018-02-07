# Roomballot Scripts
## ballotParser.py
### Importing Users
Given a spreadsheet from Accommodation, saved into csv format (which I imagine will stay pretty consistent year-on-year), run:

    ballotParser.py /path/to/spreadsheet.csv

This will generate something that can be imported using phpMyAdmin.

### Generating AuthGroup File
Use:

    ballotParser.py --authgroups True /path/to/spreadsheet.csv

to generate an AuthGroup file that can be used in conjunction with .htaccess
