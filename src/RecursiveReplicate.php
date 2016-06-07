<?php

namespace Ajreading\RecursiveReplicate;

trait RecursiveReplicateTrait
{
    /**
     * Gets relationships the instance should be replicated with.
     *
     * @var array
     */
    public function replicatesWith()
    {
        return property_exists($this, 'replicatesWith') ? $this->replicatesWith : [];
    }

    /**
     * Clone the model into a new, non-existing instance, including all
     * relationships, recursively.
     *
     * @param  array|null  $except
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function replicate(array $except = null)
    {
        // Replicate the model instance
        $replication = parent::replicate($except);

        // Get an array of related models to replicate
        $relations = array_intersect_key(
            $replication->relations,
            array_flip($this->replicatesWith())
        );

        // Save the replicated model, to obtain a primary key
        $replication->save();

        foreach ($this->getRelations() as $relation => $child_models) {
            // Check the relation is in the allowed list
            if (in_array($relation, array_keys($relations))) {
                foreach ($child_models as $model) {
                    // Replicate the child
                    $child_model = $model->replicate();

                    // Determine the relationship type
                    $relationship = $this->getRelationshipType($replication->$relation());

                    // Attach HasMany relationships
                    if ($relationship == 'HasMany') {
                        $replication->$relation()->save($child_model);
                    }
                }
            }
        }

        return $replication;
    }

    /**
     * Get the relationship type.
     *
     * @param mixed $instance
     *
     * @return string
     */
    private function getRelationshipType($instance)
    {
        $name = get_class($instance);

        $split = explode('\\', $name);

        return end($split);
    }
}
