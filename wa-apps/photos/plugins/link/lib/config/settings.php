<?php

return array (
    'feedback' => array(
        'title' => _wp('Ask for technical support'),
        'description'   => _wp('Click on the link to contact the developer.'),
        'control_type' => waHtmlControl::CUSTOM.' '.'photosLinkPlugin::getFeedbackControl',
        'subject'       => 'info_settings',
    ),
);
