<?php

http_response_code(204);
Header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
Header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date dans le passé
Header('Pragma: no-cache');

clearToken();

return true;
