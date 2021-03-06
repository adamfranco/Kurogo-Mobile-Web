<?php

/*
 * Copyright © 2010 - 2012 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/**
 * Anonymous User
 * @package Authentication
 */
class AnonymousUser extends User
{
    public function __toString() {
        return 'anonymous';
    }

    public function __construct()
    {
    }

    public function getUserHash() {
        return '';
    }
 
    public function setUserData($key, $value) {
        return false;
    }

    public function getUserData($key=null) {
        return null;
    }
 
}

