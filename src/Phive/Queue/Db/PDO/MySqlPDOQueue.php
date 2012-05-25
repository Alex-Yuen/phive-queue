<?php

namespace Phive\Queue\Db\PDO;

use Phive\Queue\AdvancedQueueInterface;

class MySqlPDOQueue extends AbstractPDOQueue
{
    public function __construct(\PDO $conn, $tableName)
    {
        if ('mysql' != $conn->getAttribute(\PDO::ATTR_DRIVER_NAME)) {
            throw new \InvalidArgumentException('Invalid PDO driver specified.');
        }

        parent::__construct($conn, $tableName);
    }

    /**
     * @see QueueInterface::pop()
     */
    public function pop()
    {
        $sql = 'SELECT id, item FROM '.$this->tableName
            .' WHERE eta <= :eta ORDER BY eta, id LIMIT 1 FOR UPDATE';

        $stmt = $this->prepareStatement($sql);
        $stmt->bindValue(':eta', time(), \PDO::PARAM_INT);

        $this->conn->beginTransaction();

        try {
            $this->executeStatement($stmt);

            if ($row = $stmt->fetch()) {
                $stmt->closeCursor();

                $sql = 'DELETE FROM '.$this->tableName.' WHERE id = :id';

                $stmt = $this->prepareStatement($sql);
                $stmt->bindValue(':id', $row['id'], \PDO::PARAM_INT);

                $this->executeStatement($stmt);
            }

            $this->conn->commit();
        } catch (\Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }

        return $row ? $row['item'] : false;
    }
}
