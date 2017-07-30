<?php

$appDb = new NotORM(new PDO($appDbPDOConnection, $appDbUser, $appDbPassword) );
$appDb->debug = defined('DEBUG') && DEBUG;
// Cleanup for security reason
unset($appDbPDOConnection);
unset($appDbUser);
unset($appDbPassword);

