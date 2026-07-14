<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'spent_on' => ['required', 'date'],
            'category' => ['required', Rule::in(Expense::CATEGORIES)],
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $request->user()->expenses()->create($data);

        return back()->with('status', 'Expense added.');
    }

    public function destroy(Request $request, Expense $expense)
    {
        abort_unless($expense->user_id === $request->user()->id, 403);
        $expense->delete();

        return back()->with('status', 'Expense removed.');
    }
}
