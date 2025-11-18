import * as React from "react"
import { cn } from "@/lib/utils"

function Badge({ className, variant = "default", ...props }) {
  return (
    <div
      className={cn(
        "inline-flex items-center rounded-md border border-gray-200 px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-gray-950 focus:ring-offset-2",
        {
          "border-transparent bg-gray-900 text-gray-50 shadow hover:bg-gray-900/80": variant === "default",
          "border-transparent bg-gray-100 text-gray-900 hover:bg-gray-100/80": variant === "secondary",
          "border-transparent bg-red-500 text-gray-50 shadow hover:bg-red-500/80": variant === "destructive",
          "text-gray-950": variant === "outline",
        },
        className
      )}
      {...props}
    />
  )
}

export { Badge }
