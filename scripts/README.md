#Using ballotParser.py

Given a spreadsheet from Accommodation, saved into csv format (which I imagine will stay pretty consistent year-on-year), run:

    ballotParser.py /path/to/spreadsheet.csv

to generate something that can be imported using phpMyAdmin. Use:

    ballotParser.py --authgroups True /path/to/spreadsheet.csv

to generate an AuthGroup file to be used in conjunction with the .htaccess
