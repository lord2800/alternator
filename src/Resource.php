<?php
namespace Alternator;

class Resource {
    private $pdo;

    public function __construct(\PDO $pdo) {
        $this->pdo = $pdo;
        $pdo->setAttribute(\PDO::ATTR_STATEMENT_CLASS, ['Alternator\\JsonablePDOStatement', []]);
    }

    public function create($name, $obj) {

    }

    public function save($name, $obj) {

    }

    public function delete($name, $id) {

    }

    public function retrieve($name, $id = 0) {
        return empty($id) ? self::retrieveAll($name) : self::retrieveOne($name, $id);
    }

    public function retrieveAll($name, array $filters = array(), array $options = array()) {
        $where = '1=1';
        foreach($filters as $type => $filter) {
            $where .= sprintf(' %s `%s` %s %s ', strtoupper($type), $filter['lhs'], $filter['op'], $this->pdo->quote($filter['rhs']));
        }
        $where .= implode(' ', $options);
        $statement = $this->pdo->prepare('SELECT * FROM ' . $name . ' WHERE ' . $where);
        $statement->execute();
        return $statement;
    }
    public function retrieveOne($name, $id) {
        $filters = ['and' => [
            'lhs' => 'id',
            'op'  => '=',
            'rhs' => $id
        ]];
        return self::retrieveAll($name, $filters, ['limit 1']);
    }
}
