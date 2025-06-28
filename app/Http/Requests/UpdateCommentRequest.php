<?php

namespace App\Http\Requests;

// Backward compatibility layer - extends the modular UpdateCommentRequest
class UpdateCommentRequest extends \App\Modules\Comments\Requests\UpdateCommentRequest
{
    // This class serves as a backward compatibility layer
    // All functionality is inherited from the modular UpdateCommentRequest
}
