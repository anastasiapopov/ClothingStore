<?php
session_start();
session_unset();
session_destroy();

header("Location: /turyshop/index.php");
exit;
