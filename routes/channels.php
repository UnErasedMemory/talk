<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('room.{roomId}', function ($user, $roomId) {
    return ['id' => $user->id, 'name' => $user->name];
});
