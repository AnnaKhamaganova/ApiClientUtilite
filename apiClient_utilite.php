<?php

interface ApiClientInterface {
    public function getSources(int $page);
    public function postSource(string $name, string $description, string $sourceUrl, string $attr1, string $attr2): array;
    public function deleteSource(int $id): string;
}

class ApiClient implements ApiClientInterface {
    private string $url;

    public function __construct(string $url) {
        $this->url = $url;
    }

    public function getSources(int $page) {
        $urlWithPage = "{$this->url}?page=$page";
        
        $ch = curl_init($urlWithPage);
 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
 
        if (curl_errno($ch)) {
            throw new Exception('Ошибка cURL: ' . curl_error($ch));
        }

        echo $this->httpCodeCheck($httpCode);
 
        curl_close($ch);

        $sources = json_decode($response, true);

        if ($sources["member"] === null) {
            throw new Exception("Ресурсы не найдены");
        } else {
            echo "Ресурсы:\n";
            foreach ($sources["member"] as $source) {
                echo "Id: $source[id], Название: $source[name]\n";
            }
        }  
    }

    public function postSource(string $name, string $description, string $sourceUrl, string $attr1, string $attr2): array {
        $data = [
            "name" => $name,
            "description" => $description,
            "url" => $sourceUrl,
            "attr1" => $attr1,
            "attr2" => $attr2,
            "platform" => '/api/platforms/1',
        ];
        
        $ch = curl_init($this->url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/ld+json',
            'Content-Type: application/ld+json; charset=utf-8',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception('Ошибка cURL: ' . curl_error($ch));
        }

        echo $this->httpCodeCheck($httpCode);

        curl_close($ch);

        return json_decode($response, true);
    }

    public function deleteSource(int $id): string {
        $urlWithId = "{$this->url}/$id";

        $ch = curl_init($urlWithId);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new Exception('Ошибка cURL: ' . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);

        return $this->httpCodeCheck($httpCode);
    }

    public function httpCodeCheck(int $httpCode): string {
        switch ($httpCode) {
            case 200:
                return "Запрос выполнен.\n";
            case 201:
                return "Ресурс успешно добавлен.\n";
            case 204:
                return "Ресурс успешно удален.\n";
            case 400:
                throw new Exception('Введены некорректные данные');
            case 404:
                throw new Exception('Ресурс с таким ID не найден.');
            default:
                throw new Exception('Получен неожиданный код ответа: '. $httpCode);
        }
    }

    public function help() {
        echo "Использование: php script.php <команда> [аргументы]\n";
        echo "Доступные команды:\n";
        echo ' - get <page>'. " - команда для получения списка ресурсов на указанной странице;\n";
        echo ' - post <name> <description> <url> <attr1> <attr2>'. " - команда для добавления нового ресурса;\n";
        echo ' - delete <id>'. " - команда для удаления существующего ресурса по id;\n";
    }
}

$apiUrl = "http://6d21d1646ba0.vps.myjino.ru/api/sources";
$apiClient = new ApiClient($apiUrl);

if ($argc < 2) {
    $apiClient->help();
    exit(1);
}

$command = $argv[1];

try {
    switch ($command) {
        case 'get':
            if ($argc != 3) {
                throw new Exception("Использование: php script.php get <page>\n");
            }
            $page = (int)$argv[2];
            $apiClient->getSources($page);
            break;

        case 'post':
            if ($argc != 7) {
                throw new Exception("Использование: php script.php post <name> <description> <url> <attr1> <attr2>\n");
            }
            $name = $argv[2];
            $description = $argv[3];
            $sourceUrl = $argv[4];
            $attr1 = $argv[5];
            $attr2 = $argv[6];

            $response = $apiClient->postSource($name, $description, $sourceUrl, $attr1, $attr2);
            print_r($response);
            break;

        case 'delete':
            if ($argc != 3) {
                throw new Exception("Использование: php script.php delete <id>\n");
            }
            $id = (int)$argv[2];
            echo $apiClient->deleteSource($id);
            break;

        case 'help':
            $apiClient->help();
            
        default:
            $apiClient->help();
    }
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage() . "\n";
}