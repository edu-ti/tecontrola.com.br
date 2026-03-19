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
    $baseUrl = getAsaasBaseUrl();
    
    $ch = curl_init("$baseUrl/customers");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, getAsaasHeaders());
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'name' => $group_data['name'] ?? 'Grupo ' . uniqid(),
        // Você pode passar e-mail, cpf/cnpj conforme a necessidade real do seu formulário
    ]));
    
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

function createAsaasSubscription($customer_id, $plan_data) {
    $baseUrl = getAsaasBaseUrl();
    
    $ch = curl_init("$baseUrl/subscriptions");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, getAsaasHeaders());
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'customer' => $customer_id,
        'billingType' => strtoupper($plan_data['payment_method'] ?? 'PIX'),
        'value' => (float)($plan_data['price'] ?? 29.90),
        'nextDueDate' => $plan_data['next_due_date'] ?? date('Y-m-d', strtotime('+30 days')),
        'cycle' => 'MONTHLY',
        'description' => 'Assinatura TeControla - Plano ' . ($plan_data['plan'] ?? 'Básico')
    ]));
    
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

function cancelAsaasSubscription($subscription_id) {
    $baseUrl = getAsaasBaseUrl();
    
    $ch = curl_init("$baseUrl/subscriptions/$subscription_id");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, getAsaasHeaders());
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}
