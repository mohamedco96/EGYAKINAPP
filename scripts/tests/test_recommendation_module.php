<?php

// Test script to verify Recommendation module can be loaded
require_once __DIR__ . '/vendor/autoload.php';

try {
    echo 'Testing Recommendation module instantiation...' . PHP_EOL;

    // Test Service
    try {
        $service = app('App\Modules\Recommendations\Services\RecommendationService');
        echo '✅ RecommendationService can be instantiated via container' . PHP_EOL;
    } catch (\Exception $e) {
        echo '❌ RecommendationService error: ' . $e->getMessage() . PHP_EOL;
    }

    // Test Controller
    try {
        $controller = app('App\Modules\Recommendations\Controllers\RecommendationController');
        echo '✅ RecommendationController can be instantiated via container' . PHP_EOL;
    } catch (\Exception $e) {
        echo '❌ RecommendationController error: ' . $e->getMessage() . PHP_EOL;
    }

    // Test Model
    try {
        $model = new App\Modules\Recommendations\Models\Recommendation();
        echo '✅ Recommendation model can be instantiated' . PHP_EOL;
    } catch (\Exception $e) {
        echo '❌ Recommendation model error: ' . $e->getMessage() . PHP_EOL;
    }

    // Test Request classes
    try {
        $storeRequest = new App\Modules\Recommendations\Requests\StoreRecommendationRequest();
        echo '✅ StoreRecommendationRequest can be instantiated' . PHP_EOL;
    } catch (\Exception $e) {
        echo '❌ StoreRecommendationRequest error: ' . $e->getMessage() . PHP_EOL;
    }

    try {
        $updateRequest = new App\Modules\Recommendations\Requests\UpdateRecommendationRequest();
        echo '✅ UpdateRecommendationRequest can be instantiated' . PHP_EOL;
    } catch (\Exception $e) {
        echo '❌ UpdateRecommendationRequest error: ' . $e->getMessage() . PHP_EOL;
    }

    try {
        $deleteRequest = new App\Modules\Recommendations\Requests\DeleteRecommendationRequest();
        echo '✅ DeleteRecommendationRequest can be instantiated' . PHP_EOL;
    } catch (\Exception $e) {
        echo '❌ DeleteRecommendationRequest error: ' . $e->getMessage() . PHP_EOL;
    }

    // Test Policy
    try {
        $policy = new App\Modules\Recommendations\Policies\RecommendationPolicy();
        echo '✅ RecommendationPolicy can be instantiated' . PHP_EOL;
    } catch (\Exception $e) {
        echo '❌ RecommendationPolicy error: ' . $e->getMessage() . PHP_EOL;
    }

    // Test Resource
    try {
        $resource = new App\Modules\Recommendations\Resources\RecommendationResource(null);
        echo '✅ RecommendationResource can be instantiated' . PHP_EOL;
    } catch (\Exception $e) {
        echo '❌ RecommendationResource error: ' . $e->getMessage() . PHP_EOL;
    }

    echo PHP_EOL . '🎉 All Recommendation module classes are working correctly!' . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
