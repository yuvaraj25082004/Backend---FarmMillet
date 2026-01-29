<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Set content type
header('Content-Type: application/json');

// Error handling
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error: $errstr in $errfile on line $errline");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred'
    ]);
    exit;
});

// Get request method and URI
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove base path (adjust if needed)
$basePath = '/Millets/api';
$uri = str_replace($basePath, '', $uri);
$uri = trim($uri, '/');

// Split URI into parts
$parts = explode('/', $uri);

// Import controllers
use App\Controllers\Auth\FarmerAuthController;
use App\Controllers\Auth\SHGAuthController;
use App\Controllers\Auth\ConsumerAuthController;
use App\Controllers\FarmerSupplyController;
use App\Controllers\SHGController;
use App\Controllers\ConsumerController;
use App\Controllers\TraceabilityController;
use App\Controllers\RazorpayController;
use App\Helpers\Response;

try {
    // ========================================
    // AUTHENTICATION ROUTES
    // ========================================
    
    // Farmer Auth
    if ($parts[0] === 'auth' && $parts[1] === 'farmer') {
        if ($method === 'POST' && $parts[2] === 'register') {
            FarmerAuthController::register();
        } elseif ($method === 'POST' && $parts[2] === 'verify-otp') {
            FarmerAuthController::verifyOTP();
        } elseif ($method === 'POST' && $parts[2] === 'login') {
            FarmerAuthController::login();
        } elseif ($method === 'POST' && $parts[2] === 'forgot-password') {
            FarmerAuthController::forgotPassword();
        } elseif ($method === 'POST' && $parts[2] === 'reset-password') {
            FarmerAuthController::resetPassword();
        }
    }
    
    // SHG Auth
    elseif ($parts[0] === 'auth' && $parts[1] === 'shg') {
        if ($method === 'POST' && $parts[2] === 'register') {
            SHGAuthController::register();
        } elseif ($method === 'POST' && $parts[2] === 'verify-otp') {
            SHGAuthController::verifyOTP();
        } elseif ($method === 'POST' && $parts[2] === 'login') {
            SHGAuthController::login();
        } elseif ($method === 'POST' && $parts[2] === 'forgot-password') {
            SHGAuthController::forgotPassword();
        } elseif ($method === 'POST' && $parts[2] === 'reset-password') {
            SHGAuthController::resetPassword();
        }
    }
    
    // Consumer Auth
    elseif ($parts[0] === 'auth' && $parts[1] === 'consumer') {
        if ($method === 'POST' && $parts[2] === 'register') {
            ConsumerAuthController::register();
        } elseif ($method === 'POST' && $parts[2] === 'verify-otp') {
            ConsumerAuthController::verifyOTP();
        } elseif ($method === 'POST' && $parts[2] === 'login') {
            ConsumerAuthController::login();
        } elseif ($method === 'POST' && $parts[2] === 'forgot-password') {
            ConsumerAuthController::forgotPassword();
        } elseif ($method === 'POST' && $parts[2] === 'reset-password') {
            ConsumerAuthController::resetPassword();
        } elseif ($method === 'GET' && $parts[2] === 'profile') {
            ConsumerAuthController::getProfile();
        } elseif ($method === 'PUT' && $parts[2] === 'profile') {
            ConsumerAuthController::updateProfile();
        }
    }
    
    // ========================================
    // FARMER ROUTES
    // ========================================
    elseif ($parts[0] === 'farmer') {
        if ($parts[1] === 'supply') {
            if ($method === 'POST') {
                FarmerSupplyController::addSupply();
            } elseif ($method === 'GET') {
                if (isset($parts[2]) && is_numeric($parts[2])) {
                    FarmerSupplyController::getSupplyById((int)$parts[2]);
                } else {
                    FarmerSupplyController::getSupplies();
                }
            }
        } elseif ($method === 'GET' && $parts[1] === 'payment-history') {
            FarmerSupplyController::getPaymentHistory();
        } elseif ($method === 'GET' && $parts[1] === 'sales-summary') {
            FarmerSupplyController::getSalesSummary();
        } elseif ($method === 'GET' && $parts[1] === 'dashboard') {
            FarmerSupplyController::getSalesSummary();
        }
    }
    
    // ========================================
    // SHG ROUTES
    // ========================================
    elseif ($parts[0] === 'shg') {
        if ($parts[1] === 'supply') {
            if ($method === 'GET' && !isset($parts[2])) {
                SHGController::viewSupplies();
            } elseif ($method === 'PUT' && $parts[2] === 'accept' && isset($parts[3])) {
                SHGController::acceptSupply((int)$parts[3]);
            } elseif ($method === 'PUT' && $parts[2] === 'complete' && isset($parts[3])) {
                SHGController::completeSupply((int)$parts[3]);
            } elseif ($method === 'POST' && $parts[2] === 'payment') {
                SHGController::recordPayment();
            }
        } elseif ($parts[1] === 'payment' && $method === 'POST') {
            SHGController::recordPayment();
        } elseif ($parts[1] === 'product' || $parts[1] === 'products') {
            if ($method === 'POST') {
                SHGController::createProduct();
            } elseif ($method === 'GET') {
                SHGController::getProducts();
            }
        } elseif ($parts[1] === 'order' && isset($parts[2]) && isset($parts[3]) && $parts[3] === 'status') {
            if ($method === 'PUT') {
                SHGController::updateOrderStatus((int)$parts[2]);
            }
        } elseif ($parts[1] === 'order' && $parts[2] === 'status' && isset($parts[3])) {
            if ($method === 'PUT') {
                SHGController::updateOrderStatus((int)$parts[3]);
            }
        } elseif ($parts[1] === 'orders' && $method === 'GET') {
            SHGController::getOrders();
        } elseif ($parts[1] === 'dashboard' && $method === 'GET') {
            SHGController::getDashboardStats();
        } elseif ($parts[1] === 'payment-history' && $method === 'GET') {
            SHGController::getPaymentHistory();
        }
    }
    
    // ========================================
    // CONSUMER ROUTES
    // ========================================
    elseif ($parts[0] === 'consumer') {
        if ($parts[1] === 'products' && $method === 'GET') {
            ConsumerController::viewProducts();
        } elseif ($parts[1] === 'product' && isset($parts[2]) && $method === 'GET') {
            ConsumerController::getProduct((int)$parts[2]);
        } elseif ($parts[1] === 'order' || $parts[1] === 'orders') {
            if ($method === 'POST') {
                ConsumerController::placeOrder();
            } elseif ($method === 'GET') {
                if (!isset($parts[2])) {
                    ConsumerController::getOrders();
                } elseif ($parts[2] && isset($parts[3]) && $parts[3] === 'track') {
                    ConsumerController::trackOrder((int)$parts[2]);
                } else {
                    Response::notFound('Consumer order sub-route not found');
                }
            } else {
                Response::error('Method not allowed', null, 405);
            }
        } else {
            Response::notFound('Consumer endpoint not found');
        }
    }
    
    // ========================================
    // TRACEABILITY ROUTES
    // ========================================
    elseif ($parts[0] === 'traceability') {
        if ($method === 'GET' && !isset($parts[1])) {
            if (isset($_GET['id'])) {
                TraceabilityController::search();
            } else {
                TraceabilityController::listAll();
            }
        } elseif ($method === 'GET' && isset($parts[1])) {
            if (is_numeric($parts[1])) {
                TraceabilityController::getById((int)$parts[1]);
            } else {
                // If it's a string (like TR-2026...), use search
                $_GET['id'] = $parts[1];
                TraceabilityController::search();
            }
        }
    }
    
    // ========================================
    // PAYMENT ROUTES
    // ========================================
    elseif ($parts[0] === 'payment') {
        if ($method === 'POST' && $parts[1] === 'order') {
            RazorpayController::createOrder();
        } elseif ($method === 'POST' && $parts[1] === 'verify') {
            RazorpayController::verifyPayment();
        } elseif ($method === 'GET' && isset($parts[1]) && is_numeric($parts[1])) {
            RazorpayController::getPaymentDetails((int)$parts[1]);
        }
    }
    elseif ($parts[0] === 'payments' && $parts[1] === 'razorpay') {
        if ($method === 'POST' && $parts[2] === 'create-order') {
            RazorpayController::createOrder();
        } elseif ($method === 'POST' && $parts[2] === 'verify') {
            RazorpayController::verifyPayment();
        } elseif ($method === 'GET' && isset($parts[2]) && is_numeric($parts[2])) {
            RazorpayController::getPaymentDetails((int)$parts[2]);
        }
    }
    
    // ========================================
    // NOT FOUND
    // ========================================
    else {
        Response::notFound('Endpoint not found');
    }

} catch (\Exception $e) {
    error_log("Router exception: " . $e->getMessage());
    Response::serverError('An unexpected error occurred');
}
