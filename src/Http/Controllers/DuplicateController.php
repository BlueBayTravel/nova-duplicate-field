<?php

namespace Jackabox\DuplicateField\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DuplicateController extends Controller
{
    /**
     * Duplicate a nova field and all of the relations defined.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function duplicate(Request $request)
    {
        $basePath = config('nova.path') === '/' ?
            config('app.url') :
            config('nova.path');

        // Replicate the model
        $model = $request->model::where('id', $request->id)->first();

        if (!$model) {
            return [
                'status'      => 404,
                'message'     => 'No model found.',
                'destination' => $basePath.'/resources/'.$request->resource.'/'
            ];
        }

        /** @var \Illuminate\Database\Eloquent\Model $model */
        $newModel = $model->replicate();

        if (isset($request->relations) && !empty($request->relations)) {
            // load the relations
            $model->load($request->relations);

            foreach ($model->getRelations() as $relation => $items) {
                // works for hasMany
                foreach ($items as $item) {
                    // clean up our models, remove the id and remove the appends
                    unset($item->id);
                    /** @var \Illuminate\Database\Eloquent\Model $item */
                    $item->setAppends([]);

                    // create a relation on the new model with the data.
                    dd($item->toArray());

                    $newModel->{$relation}()->create($item->toArray());
                }
            }
        }

        $newModel->push();

        // return response and redirect.
        return [
            'status'      => 200,
            'message'     => 'Done',
            'destination' => url($basePath.'/resources/'.$request->resource.'/'.$newModel->id)
        ];
    }
}
