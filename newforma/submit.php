<?php
header("Access-Control-Allow-Origin: *"); // Разрешить CORS (если форма и скрипт на разных доменах)
header("Content-Type: application/json; charset=UTF-8");

// Параметры подключения к БД (замените на свои)
$host = "%";
$user = "root";
$password = "gcs7jKgMJQ6R6mKW";
$database = "svo"; // Имя вашей БД

// Подключение к MySQL
$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

// Получаем данные из POST-запроса
$data = json_decode(file_get_contents("php://input"), true);

// Экранируем данные для защиты от SQL-инъекций
$full_name = $conn->real_escape_string($data['full_name']);
$birth_date = $conn->real_escape_string($data['birth_date']);
$mesto_propiski = $conn->real_escape_string($data['mesto_propiski']);
$seriy_pasport = $conn->real_escape_string($data['seriy_pasport']);
$nomer_pasport = $conn->real_escape_string($data['nomer_pasport']);
$data_obs = $conn->real_escape_string($data['data_obs']);
$kategoriya_zdor = $conn->real_escape_string($data['kategoriya_zdor']);
$rod_voisk = $conn->real_escape_string($data['rod_voisk']);
$company_id = (int)$data['company_id'];
$start_date = $conn->real_escape_string($data['start_date']);
$end_date = (int)$data['end_date'];
$data_vidachi = $conn->real_escape_string($data['data_vidachi']);
$id_vch = (int)$data['id_vch'];
$id_v = (int)$data['id_v'];

// Начинаем транзакцию (чтобы сохранить данные во всех таблицах)
$conn->begin_transaction();

try {
    // 1. Вставляем данные в `info_user`
    $sql_user = "INSERT INTO info_user (full_name, birth_date, mesto_propiski, nomer_pasport, seriy_pasport) 
                 VALUES ('$full_name', '$birth_date', '$mesto_propiski', '$nomer_pasport', '$seriy_pasport')";
    $conn->query($sql_user);
    $user_id = $conn->insert_id; // Получаем ID нового пользователя

    // 2. Вставляем данные в `Health`
    $sql_health = "INSERT INTO Health (data_obs, kategoriya_zdor, user_id) 
                   VALUES ('$data_obs', '$kategoriya_zdor', $user_id)";
    $conn->query($sql_health);

    // 3. Вставляем данные в `kontrakt`
    $sql_contract = "INSERT INTO kontrakt (start_date, end_date, company_id, user_id) 
                     VALUES ('$start_date', DATE_ADD('$start_date', INTERVAL $end_date YEAR), $company_id, $user_id)";
    $conn->query($sql_contract);

    // 4. Вставляем данные в `voen_bilet`
    $sql_bilet = "INSERT INTO voen_bilet (data_vidachi, data_nach, data_okonch, unit_id, id_vch, user_id, id_v) 
                  VALUES ('$data_vidachi', '$start_date', DATE_ADD('$start_date', INTERVAL $end_date YEAR), 
                  (SELECT unit_id FROM military_units WHERE voen_specialnost = '$rod_voisk'), $id_vch, $user_id, $id_v)";
    $conn->query($sql_bilet);

    // Если всё успешно — коммитим транзакцию
    $conn->commit();
    echo json_encode(["success" => true, "message" => "Данные сохранены!"]);
} catch (Exception $e) {
    // Если ошибка — откатываем транзакцию
    $conn->rollback();
    echo json_encode(["success" => false, "message" => "Ошибка: " . $e->getMessage()]);
}

$conn->close();
?>