# Database discover

Project to discover a relational database structure.

The main class is `DatabaseDiscover.php`. Instantiates it providing a PDO object holding the database connection data. Then use its methods to show you some data about the database.

## TODO

* Checks if there are fields as id in other tables that matches to current table field. The intent is to search for any foreign relationship (even if no foreign key is setted to field)
* Search for related tables by name.
