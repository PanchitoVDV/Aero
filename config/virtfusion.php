<?php

return [
    'url' => env('VIRTFUSION_URL', 'https://localhost'),
    'api_token' => env('VIRTFUSION_API_TOKEN', ''),
    'hypervisor_group_id' => env('VIRTFUSION_HYPERVISOR_GROUP_ID', 1),
    'default_ipv4' => 1,
    'enable_vnc' => true,
    'enable_ipv6' => true,
    'default_swap' => 512,
];
