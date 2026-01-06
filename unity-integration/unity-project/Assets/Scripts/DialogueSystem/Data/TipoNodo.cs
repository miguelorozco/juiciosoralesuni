namespace DialogueSystem.Data
{
    /// <summary>
    /// Enum que define los tipos de nodos de diálogo disponibles.
    /// Alineado con los tipos de nodos en la base de datos v2.
    /// </summary>
    public enum TipoNodo
    {
        /// <summary>
        /// Nodo de inicio del diálogo. Solo debe haber uno por diálogo.
        /// </summary>
        Inicio = 0,

        /// <summary>
        /// Nodo de desarrollo (NPC habla). Representa diálogo del personaje no jugador.
        /// </summary>
        Desarrollo = 1,

        /// <summary>
        /// Nodo de decisión (PC responde). Representa opciones del jugador.
        /// </summary>
        Decision = 2,

        /// <summary>
        /// Nodo final del diálogo. Puede haber múltiples nodos finales.
        /// </summary>
        Final = 3,

        /// <summary>
        /// Nodo de agrupación (para organizar nodos en el editor).
        /// Alineado con Pixel Crushers EntryGroup.
        /// </summary>
        Agrupacion = 4
    }
}
