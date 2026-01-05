<?php

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
| For multiplayer Spot It, we use public channels since players are guests
| without authentication. Game state is managed via cache.
|
*/

// Public channels don't require authorization callbacks.
// The game.{code} channel is public and anyone can listen to it.
