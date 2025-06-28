<?php

namespace App\Http\Controllers;

// Backward compatibility layer - extends the modular CommentController
class CommentController extends \App\Modules\Comments\Controllers\CommentController
{
    // This class serves as a backward compatibility layer
    // All functionality is inherited from the modular CommentController
}
