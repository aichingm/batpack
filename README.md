# Batpack

batpack - scriptable batch archive

## Usage

```
php batpack.php [-flags] [-s script ] file1 [file#]
```

### Options

* `-a` automatically unpack all files in the current working directory, use this if you don't call `batpack_unpack_all` in the injected script or if you don't inject a script at all.
* `-s`  inject a script in to the extraction routine. If used with `-a` the files will be unpacked in the current working directory before the script a executed.

## Scripting

Batpack does not provide any other functions than `batpack_unpack_all` and `batpack_<filename>` for all added files.

---

*call* **batpack_unpack_all** *%destination%*

unpacks all files to the given destination.

---

call **batpack_<filename>** *%destination%*

unpacks a file to the given destination.