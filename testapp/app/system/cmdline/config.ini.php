;<?php die(''); ?>
;for security reasons , don't remove or modify the first line

[responses]
soap="jsoap~jResponseSoap"

[coordplugins]
jacl2=1
jacl=1

[coordplugin_jacl2]
on_error=1
error_message="jacl2~errors.action.right.needed"
on_error_action="jelix~error:badright"
[coordplugin_jacl]
on_error=1
error_message="jacl~errors.action.right.needed"
on_error_action="jelix~error:badright"
