# Realm files

In order to use these files, update **tenant.conf.php** file and add `realm#dir`.
Also remove `realm` from the file, otherwise it takes priority.

```diff
+// Read realms from realms/ directory
+'realm#dir' => 'realms/',

-// Remove embedded realms, these would take priority
-'realm' => [
-	…
-],
```

Files in this directory must be named after the realm.
So if your realm is **example.com**, create a file named **example.com.conf.php**.
The **.dist.conf.php
This file should start with `<?php return [` so that it returns the array when included.
