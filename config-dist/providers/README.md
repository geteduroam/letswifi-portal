# Provider files

In order to use these files, update **tenant.conf.php** file and add `provider#dir`.
Also remove `provider` from the file, otherwise it takes priority.

```diff
+// Read providers from providers/ directory
+'provider#dir' => 'providers/',

-// Remove embedded providers, these would take priority
-'provider' => [
-	…
-],
```

Files in this directory must be named after the provider.
So if your provider is **example.com**, create a file named **example.com.conf.php**.
This file should start with `<?php return [` so that it returns the array when included.
