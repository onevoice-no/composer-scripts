composer-scripts
================

This repo contains common scripts for [Composer PHP Dependency Manager](http://getcomposer.org/).

**Composer script hooks**

All scripts in this package implements a PHP callback (defined as a static method) 
[which Composer is able to invoke](http://getcomposer.org/doc/articles/scripts.md#defining-scripts).

You activate `composer-scripts` by adding following objects to `composer.json`

```json 
"repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/onevoice-no/composer-scripts.git"
        }        
],
"require": {
        "onevoice/composer-scripts": "<VERSION>"
},
"scripts": {
  "post-update-cmd": "OneVoice\\Composer\\Scripts::perform",
  "post-install-cmd": "OneVoice\\Composer\\Scripts::perform"
}
```

Use `<VERSION>` "dev-master" to track master branch, or select one of these 
[tags](https://github.com/onevoice-no/composer-scripts/tags).

**Custom vendor-dir**

If a custom `vendor-dir` is given (see [config](http://getcomposer.org/doc/04-schema.md#config)), 
add the following object to "extra":

```json
"extra": 
{ 
  "package-dir" : "/path/to/root/package"
}
```

to `composer.json`.

**"delete" script**

Packages are often deployed with files which dependent projects do not need or care about. 
Enable this script by adding following objects to `composer.json`.

Script definition example:

```json
"delete" : {
  "<vendor>/<package>": { 
     "include": [
        "path/to/folder",
        "path/to/sub/folder"
      ],
     "exclude": [
        "path/to/folder/file1.php",
        "path/to/sub/folder/file2.php"
      ]
  }
}
```

The script also accepts `"*"` as selector for *all files*:

```json
"delete" : {
  "<vendor>/<package>": "*"
}
```

or

```json
"delete" : {
  "<vendor>/<package>": { 
     "include": "*",
     "exclude": "*"
  }
}
```

*Note*: Selectors like `"path\to\file*` are not supported (yet).
