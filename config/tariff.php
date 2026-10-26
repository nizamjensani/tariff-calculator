<?php

/*
|--------------------------------------------------------------------------
| Electricity tariff
|--------------------------------------------------------------------------
|
| Rates are stored in sen per kWh (integers) so bills are calculated
| exactly, without floating point rounding errors.
|
| Each building type has an ordered list of blocks. "up_to" is the upper
| kWh limit of the block (inclusive); null means "everything above".
|
| Residential: first 200 kWh @ RM 0.45, every kWh after that @ RM 0.97
| Commercial:  first 200 kWh @ RM 0.89, every kWh after that @ RM 1.13
|
*/

return [

    'residential' => [
        ['up_to' => 200, 'rate_sen' => 45],
        ['up_to' => null, 'rate_sen' => 97],
    ],

    'commercial' => [
        ['up_to' => 200, 'rate_sen' => 89],
        ['up_to' => null, 'rate_sen' => 113],
    ],

];
