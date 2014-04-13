<?php
namespace Alternator;

class Resource {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
        $pdo->setAttribute(\PDO::ATTR_STATEMENT_CLASS, ['Alternator\\JsonablePDOStatement', []]);
    }

    public function retrieveAll($name, array $filters = array(), array $options = array()) {
        $where = '1=1';
        foreach($filters as $type => $filter) {
            $where .= sprintf(' %s %s%s%s ', strtoupper($type), $filter['lhs'], $filter['op'], $this->pdo->quote($filter['rhs']));
        }
        $where .= implode(' ', $options);
        $statement = $this->pdo->prepare('SELECT * FROM ' . $name . ' WHERE ' . $where);
        $statement->execute();
        return $statement;
    }
    public function retrieveOne($name, array $filters = array()) {
        return self::retrieveAll($name, $filters, ['limit 1']);
    }
}
