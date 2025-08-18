<?php

namespace cavWP\Networks;

class Register
{
   public function __construct()
   {
      new Admin_User();
      new Plugin_Options();
   }
}
