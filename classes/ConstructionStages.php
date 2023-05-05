<?php

class ConstructionStages
{
    private $db;

    public function __construct()
    {
        $this->db = Api::getDb();
    }

    /**
     * @return array|false
     */
    public function getAll()
    {
        $stmt = $this->db->prepare("
			SELECT
				ID as id,
				name, 
				strftime('%Y-%m-%dT%H:%M:%SZ', start_date) as startDate,
				strftime('%Y-%m-%dT%H:%M:%SZ', end_date) as endDate,
				duration,
				durationUnit,
				color,
				externalId,
				status
			FROM construction_stages
		");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param $id
     * @return array|false
     */
    public function getSingle($id)
    {
        $stmt = $this->db->prepare("
			SELECT
				ID as id,
				name, 
				strftime('%Y-%m-%dT%H:%M:%SZ', start_date) as startDate,
				strftime('%Y-%m-%dT%H:%M:%SZ', end_date) as endDate,
				duration,
				durationUnit,
				color,
				externalId,
				status
			FROM construction_stages
			WHERE ID = :id
		");
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param ConstructionStagesCreate $data
     * @return array|false
     */
    public function post(ConstructionStagesCreate $data)
    {
        if (!$data->validate()) {
            return $data->errors;
        }

        $data->calculateDuration();

        $stmt = $this->db->prepare("
			INSERT INTO construction_stages
			    (name, start_date, end_date, duration, durationUnit, color, externalId, status)
			    VALUES (:name, :start_date, :end_date, :duration, :durationUnit, :color, :externalId, :status)
			");
        $stmt->execute([
            'name' => $data->name,
            'start_date' => $data->startDate,
            'end_date' => $data->endDate,
            'duration' => $data->duration,
            'durationUnit' => $data->durationUnit,
            'color' => $data->color,
            'externalId' => $data->externalId,
            'status' => $data->status ?? 'NEW',
        ]);
        return $this->getSingle($this->db->lastInsertId());
    }

    /**
     * @param ConstructionStagesUpdate $data
     * @param $id
     * @return array|false
     */
    public function update(ConstructionStagesUpdate $data, $id)
    {
        if (!$data->validate()) {
            return $data->errors;
        }

        $data->calculateDuration();

        $values = [];
        $bindings = ['id' => $id];
        foreach (get_object_vars($data) as $name => $value) {
            if (!empty($value)) {
                if (in_array($name, ['startDate', 'endDate'])) {
                    $name = $this->convertToSnakeCase($name);
                }
                $values[] = " $name = :$name";
                $bindings[$name] = $value;
            }
        }
        $query = "UPDATE construction_stages SET " . implode(',', $values) . " WHERE ID = :id";
        $stmt = $this->db->prepare($query);
        $stmt->execute($bindings);
        return $this->getSingle($id);
    }

    /**
     * @param $id
     * @return true[]
     */
    public function delete($id)
    {
        $stmt = $this->db->prepare("UPDATE construction_stages SET status = :status WHERE ID = :id");
        $stmt->execute([
            'id' => $id,
            'status' => 'DELETED',
        ]);
        return ['success' => true];
    }

    /**
     * @param $value
     * @return mixed|string
     */
    public function convertToSnakeCase($value)
    {
        if (!ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));

            $value = strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1' . '_', $value));
        }

        return $value;
    }
}