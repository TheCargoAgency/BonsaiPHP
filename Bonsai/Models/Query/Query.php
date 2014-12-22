<?php

namespace Bonsai\Models\Query;

use Bonsai\Exception\QueryBuilderException;

class Query
{
    const SELECT = 'SELECT ';
    const ALL_FIELDS = '* ';
    const FIELD_SEPARATOR = ', ';
    const FROM = 'FROM ';
    const GROUP = 'GROUP BY ';
    const ORDER = 'ORDER BY ';
    const LIMIT = 'LIMIT ';
    const OFFSET = 'OFFSET ';    
    const WHERE = 'WHERE ';
    const WHERE_AND = 'AND ';
    const WHERE_OR = 'OR ';
    const WHERE_IN = ' IN ';
    const WHERE_LIKE = ' LIKE ';
    const EQUAL = ' = ';
    const NOT_EQUAL = ' <> ';
    const GREATER_EQUAL = ' >= ';
    const LESSER_EQUAL = ' <= ';
    const GREATER = ' > ';
    const LESSER = ' > ';
    const IS_NOT = ' IS NOT ';
    const NULL = 'NULL ';
    const LEFT_JOIN = 'LEFT JOIN ';
    const RIGHT_JOIN = 'RIGHT JOIN ';
    const INNER_JOIN = 'INNER JOIN ';
    const OUTER_JOIN = 'OUTER JOIN ';
    const JOIN = 'JOIN ';
    const JOIN_CONDITION = 'ON ';
    const ALIAS = ' AS ';
    const SORT_ASC = ' ASC';
    const SORT_DESC = ' DESC';
    const INDENT = '    ';
}