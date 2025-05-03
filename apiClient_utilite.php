<?php

// Интерфейс для API-клиента
interface ApiClientInterface {
    public function getSources(): array;
    public function postSourse(array $data): array;
    public function deleteSource(int $id): string;
}