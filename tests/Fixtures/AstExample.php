<?php

$user = UserFactory::make();
$user->save();
logger($user);
