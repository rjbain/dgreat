<?php

if (getenv('LANDO_APP_NAME') !== 'dgreataisolr') {
  throw new RuntimeException('This setup is only for the dgreataisolr local environment.');
}
$role = \Drupal\user\Entity\Role::load('authenticated');
$role->grantPermission('access myusf site chat')->grantPermission('access deepchat api')->save();
echo "Local chatbot enabled for signed-in users at /ask-myusf.\n";
