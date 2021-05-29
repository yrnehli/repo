rm Packages.bz2
dpkg-scanpackages -m ./debs > Packages
php addDepictions.php
bzip2 Packages