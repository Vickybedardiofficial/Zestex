<?php

namespace App\Http\Controllers\Admin\Business;

use App\Models\BusinessAccount;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class BusinessAccountController extends Controller
{
	public function index(Request $request)
	{
		$status = $request->get('status', 'pending');
		$search = trim((string) $request->get('search', ''));

		$query = BusinessAccount::query()->with('user:id,first_name,last_name,email,username');

		if ($status === 'pending') {
			$query->where('is_reviewed', false);
		}
		else if ($status === 'approved') {
			$query->where('is_reviewed', true)->where('verified', true);
		}
		else if ($status === 'rejected') {
			$query->where('is_reviewed', true)->where('verified', false);
		}

		if ($search !== '') {
			$query->where(function ($q) use ($search) {
				$q->where('name', 'like', "%{$search}%")
					->orWhere('business_email', 'like', "%{$search}%")
					->orWhereHas('user', function ($u) use ($search) {
						$u->where('email', 'like', "%{$search}%")
							->orWhere('username', 'like', "%{$search}%")
							->orWhere('first_name', 'like', "%{$search}%")
							->orWhere('last_name', 'like', "%{$search}%");
					});
			});
		}

		$accounts = $query->orderByDesc('updated_at')->paginate(30)->withQueryString();

		return view('apps.mpa.admin.business.accounts.index.index', [
			'accounts' => $accounts,
			'filters' => [
				'status' => $status,
				'search' => $search,
			],
		]);
	}

	public function approve(Request $request, int $accountId)
	{
		$account = BusinessAccount::findOrFail($accountId);

		$account->update([
			'is_reviewed' => true,
			'verified' => true,
			'updated_at' => now(),
		]);

		return redirect()->back();
	}

	public function reject(Request $request, int $accountId)
	{
		$account = BusinessAccount::findOrFail($accountId);

		$account->update([
			'is_reviewed' => true,
			'verified' => false,
			'updated_at' => now(),
		]);

		return redirect()->back();
	}
}

