<?php
include_once 'base.php';

echo Gearman_Deliver::add('GearmanTest', array(), array());