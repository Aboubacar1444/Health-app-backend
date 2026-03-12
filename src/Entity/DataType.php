<?php

namespace App\Entity;

enum DataType: string
{
    case STRING = 'STRING';
    case INTEGER = 'INTEGER';
    case BOOLEAN = 'BOOLEAN';
    case JSON = 'JSON';
    case DECIMAL = 'DECIMAL';
    case DATE = 'DATE';
    case DATETIME = 'DATETIME';
}