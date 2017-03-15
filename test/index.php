<?php
set_time_limit(0);
require_once('simpletest/1.1.0/autorun.php');
foreach ( glob("test_fuseboxy_*.php") as $file ) include $file;