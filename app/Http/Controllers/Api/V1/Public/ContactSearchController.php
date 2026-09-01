<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Domain\Contacts\Models\Contact;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ContactResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContactSearchController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Contact::with(['company', 'emails', 'phones']);

        if ($request->filled('job_title')) {
            $query->where('job_title', 'LIKE', '%' . $request->input('job_title') . '%');
        }

        if ($request->filled('department')) {
            $query->where('department', $request->input('department'));
        }

        if ($request->filled('seniority')) {
            $query->where('seniority', $request->input('seniority'));
        }

        if ($request->filled('domain')) {
            $domainInput = $request->input('domain');
            $query->whereHas('company.domains', function ($q) use ($domainInput) {
                $q->where('domain', 'LIKE', '%' . $domainInput . '%')
                  ->orWhere('normalized_domain', 'LIKE', '%' . $domainInput . '%');
            });
        }

        $perPage = min((int) $request->input('per_page', 20), 100);

        return ContactResource::collection($query->paginate($perPage));
    }
}
