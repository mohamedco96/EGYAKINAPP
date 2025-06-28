<?php

namespace App\Http\Requests;

// Backward compatibility layer - extends the modular StoreCommentRequest
class StoreCommentRequest extends \App\Modules\Comments\Requests\StoreCommentRequest
{
    // This class serves as a backward compatibility layer
    // All functionality is inherited from the modular StoreCommentRequest
}
