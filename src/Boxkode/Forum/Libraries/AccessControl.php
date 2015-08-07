<?php namespace Boxkode\Forum\Libraries;

use App;

class AccessControl {

    public static function check($context, $permission, $abort = true)
    {
        // Fetch the current user
        $user_callback = config('forum.integration.current_user');
        $user = $user_callback();

        // Check for access permission
        $access_callback = config('forum.permissions.access_tag');
        $permission_granted = $access_callback($context, $user);

        if ($permission_granted && ($permission != 'access_tag'))
        {
            // Check for action permission
            $action_callback = config('forum.permissions.' . $permission);
            $permission_granted = $action_callback($context, $user);
        }

        if (!$permission_granted && $abort)
        {
            $denied_callback = config('forum.integration.process_denied');
            $denied_callback($context, $user);
        }

        return $permission_granted;
    }

}
