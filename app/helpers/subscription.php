<?php
// app/helpers/subscription.php

function getAsaasBaseUrl() {
    $env = $_ENV['ASAAS_ENVIRONMENT'] ?? 'sandbox';
    return $env === 'production' ? 'https://api.asaas.com/v3' : 'https://sandbox.asaas.com/api/v3';
}

function getAsaasHeaders() {
    return [
        'access_token: ' . ($_ENV['ASAAS_API_KEY'] ?? ''),
        'Content-Type: application/json'
    ];
}

function asaasRequest($method, $endpoint, $body = null) {
    $baseUrl = getAsaasBaseUrl();
    $url = $baseUrl . $endpoint;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, getAsaasHeaders());
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    
    $response = curl_exec($ch);
    
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("Falha na requisição cURL para Asaas: " . $error);
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $decoded = json_decode($response, true);
    
    if ($httpCode >= 400) {
        $errorMessage = "Erro Asaas HTTP $httpCode.";
        if (isset($decoded['errors']) && is_array($decoded['errors'])) {
            $errText = [];
            foreach ($decoded['errors'] as $e) {
                $errText[] = $e['description'] ?? 'Desconhecido';
            }
            $errorMessage .= " Detalhes: " . implode(', ', $errText);
        } else {
            $errorMessage .= " Corpo: " . $response;
        }
        throw new Exception($errorMessage);
    }
    
    return $decoded;
}

function checkSubscriptionStatus($group_id, $pdo) {
    if (!$pdo) return null;
    $stmt = $pdo->prepare("SELECT subscription_status, trial_ends_at, next_due_date FROM `groups` WHERE id = ?");
    $stmt->execute([$group_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function isGroupBlocked($group_id, $pdo) {
    $status = checkSubscriptionStatus($group_id, $pdo);
    return $status && $status['subscription_status'] === 'blocked';
}

function createAsaasCustomer($group_data) {
    if (empty($group_data['name']) || empty($group_data['email'])) {
        throw new Exception("Nome e E-mail são obrigatórios para criar cliente no Asaas.");
    }

    $payload = [
        'name' => $group_data['name'],
        'email' => $group_data['email'],
    ];

    if (!empty($group_data['cpfCnpj'])) {
        $payload['cpfCnpj'] = $group_data['cpfCnpj'];
    }
    if (!empty($group_data['mobilePhone'])) {
        $payload['mobilePhone'] = $group_data['mobilePhone'];
    }

    if (isset($group_data['groupType']) && $group_data['groupType'] === 'empresa') {
        $payload['personType'] = 'JURIDICA';
    } else {
        $payload['personType'] = 'FISICA';
    }

    return asaasRequest('POST', '/customers', $payload);
}

function createAsaasSubscription($customer_id, $plan_data) {
    $payload = [
        'customer' => $customer_id,
        'billingType' => strtoupper($plan_data['payment_method'] ?? 'PIX'),
        'value' => (float)($plan_data['price'] ?? 29.90),
        'nextDueDate' => $plan_data['next_due_date'] ?? date('Y-m-d', strtotime('+30 days')),
        'cycle' => 'MONTHLY',
        'description' => 'Assinatura TeControla - Plano ' . ($plan_data['plan'] ?? 'Básico'),
        'externalReference' => (string)($plan_data['group_id'] ?? ''),
        'fine' => [
            'value' => 2,
            'type' => 'PERCENTAGE'
        ],
        'interest' => [
            'value' => 1,
            'type' => 'PERCENTAGE'
        ]
    ];
    
    return asaasRequest('POST', '/subscriptions', $payload);
}

function cancelAsaasSubscription($subscription_id) {
    return asaasRequest('DELETE', "/subscriptions/$subscription_id");
}

function getAsaasSubscription($subscription_id) {
    return asaasRequest('GET', "/subscriptions/$subscription_id");
}

function restoreAsaasSubscription($subscription_id) {
    return asaasRequest('POST', "/subscriptions/$subscription_id/restore");
}

function getAsaasSubscriptionPayments($subscription_id) {
    return asaasRequest('GET', "/payments?subscription=$subscription_id");
}
