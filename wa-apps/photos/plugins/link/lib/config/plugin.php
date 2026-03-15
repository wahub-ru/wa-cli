<?php
return array (
    'name' => _wp('Link for the photo'),
    'description' => _wp('Adds a link to each photo.'),
    'img' => 'img/link16.png',
    'version' => '1.0.0',
    'vendor' => '964801',
    'handlers' =>
        array (
            'backend_photo' => 'backendPhoto',
            'photo_delete'  => 'photoDelete',
        ),
);
