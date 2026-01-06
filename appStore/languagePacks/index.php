<?php
if(!isset($appID)){$appID = 1;} //dont put this in session. apps may be embedded in apps and we might want the parent one

header('HTTP/1.1 401 Unauthorized');
?>