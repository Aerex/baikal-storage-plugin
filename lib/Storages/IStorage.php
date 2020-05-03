<?php

namespace Aerex\BaikalStorage\Storages;

use Sabre\VObject\Component\VCalendar as Calendar;

interface IStorage {
 public function save(Calendar $c);
 public function refresh();
 public function getConfig();
 public function setRawConfigs();
}
