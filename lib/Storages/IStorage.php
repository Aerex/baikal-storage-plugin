<?php

namespace Aerex\BaikalStorage\Storages;

use Sabre\VObject\Component\VCalendar as Calendar;

interface IStorage {
 public function save(Calendar $c, string $displayname);
 public function remove($uid);
 public function refresh();
 public function getConfigBrowser();
 public function updateConfigs($postData);
}
