RSC Plugin for CakePHP
========================

Rackspace Cloud API - Implemented in a simple CakePHP manner.

Install
-----------------------

Install as a submodule (if your project is a git repo)

```
cd project
git submodule add https://github.com/zeroasterisk/RSC-CakePHP-Plugin.git app/Plugin/RSC
git submodule update --init --recursive
```

Install as a clone/copy of this project

```
cd project
git clone https://github.com/zeroasterisk/RSC-CakePHP-Plugin.git app/Plugin/RSC
cd app/Plugin/RSC
git submodule update --init --recursive
```

Manual Installation

* Download this plugin into `app/Plugin/RSC`
* Download (php-opencloud)[https://github.com/rackspace/php-opencloud] into `app/Plugin/RSC/Vendor/php-opencloud`


Configure
----------------------

Ensure the plugin is loaded in `app/Config/bootstrap.php` by calling `CakePlugin::load('RSC');`

```
echo "CakePlugin::load('RSC');" >> `app/Config/bootstrap.php`
```

Copy the example config file over, and edit

```
cp app/Plugin/RSC/Config/rsc.example.php app/Config/rsc.php
```

Usage RSC Files
-----------------------

Setup

```
// gets the Model for use, or you can include in $this->uses()
$this->RSCFile = ClassRegistry::init('RSC.RSCFile');
```

Uploading a file

```
// uploads a file
//   auto-creates the container if it doesn't exist
//   automatically uses the basename($filepath) as the filename (no dirs)
$fileObject = $this->RSCFile->upload($filepath, 'my-cool-container');
$url = $fileObject->PublicURL();

// uploads the same file to a custom filename with paths/dirs
$fileObject = $this->RSCFile->upload($filepath, 'my-cool-container', 'funky/path/here/filename.png');

// you can force whatever params/headers you want, including 'name'
$fileObject = $this->RSCFile->upload($filepath, 'my-cool-container', array(
	'name' => 'funky/path/here/filename.png',
	'Access-Control-Allow-Origin' => '*',
));
```

Basic "find" functionality

```
// CakePHP API'ish [read]
$this->RSCFile->id = 'filename.png';
$fileObject = $this->RSCFile->read();
$fileObject = $this->RSCFile->read(null, 'filename.png');
$fileObject = $this->RSCFile->read(null, 'funky/path/here/filename.png');

// CakePHP API'ish [exists]
$this->RSCFile->id = 'filename.png';
$existsBool = $this->RSCFile->exists();
$existsBool = $this->RSCFile->exists('filename.png');
$existsBool = $this->RSCFile->exists('funky/path/here/filename.png');


// returns a simple list of filenames
$filenames = $this->RSCFile->findFiles('filena');

// returns a 1-dimensional array of details, including: name, size, type
$files = $this->RSCFile->findFilesWithDetails('filena');
```

Download a file

```
$successBool = $this->RSCFile->download('filename.png', 'my-cool-container', APP . 'tmp' . DS . 'myfile.png');
```

Delete files

```
// CakePHP API'ish [delete]
$this->RSCFile->id = 'filename.png';
deletedBool = $this->RSCFile->delete();
deletedBool = $this->RSCFile->delete('filename.png');
deletedBool = $this->RSCFile->delete('funky/path/here/filename.png');
```

Do your own API work
--------------------------

Once you get connected up to a container, the rest of the API setup is very
simple... so you can easily use this model to get you to the `$container`
object and then you can do your own API implementation/work from there.

```
$Container = $this->RSCFile->container('my-cool-container');
$newDataObject = $Container->DataObject();
$newDataObject->Create($params, $sourcefile);
```
